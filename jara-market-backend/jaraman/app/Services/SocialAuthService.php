<?php

namespace App\Services;

use App\Enums\UserPermissionsEnum;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Wallet;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthService
{
    /**
     * The OAuth providers supported by the application.
     */
    protected const SUPPORTED_PROVIDERS = ['google', 'facebook', 'apple'];

    /**
     * Authenticate a user via a social provider token.
     *
     * This method follows Laravel Socialite's `userFromToken` pattern for
     * stateless API authentication. The Flutter app obtains the OAuth token
     * natively and sends it here for server-side verification.
     *
     * @param  string  $provider  The OAuth provider name (google, facebook, apple).
     * @param  string  $token  The access token or ID token obtained by the mobile app.
     * @param  string  $role  The desired user role (customer or vendor).
     * @return array  User data merged with a Sanctum bearer token.
     *
     * @throws Exception
     */
    public function authenticate(string $provider, string $token, string $role): array
    {
        $this->validateProvider($provider);

        $socialUser = $this->getSocialUser($provider, $token);

        $user = $this->findOrCreateUser($socialUser, $provider, $role);

        return $this->issueToken($user);
    }

    /**
     * Validate that the given provider is supported.
     *
     * @throws Exception
     */
    protected function validateProvider(string $provider): void
    {
        if (! in_array($provider, self::SUPPORTED_PROVIDERS)) {
            throw new Exception(
                "The provider [{$provider}] is not supported. Supported providers: ".implode(', ', self::SUPPORTED_PROVIDERS),
                422
            );
        }
    }

    /**
     * Retrieve the social user profile from the OAuth provider.
     *
     * Uses Socialite's `userFromToken` method which verifies the token
     * with the provider and returns the user's profile data.
     *
     * @throws Exception
     */
    protected function getSocialUser(string $provider, string $token): \Laravel\Socialite\Two\User
    {
        try {
            return Socialite::driver($provider)->stateless()->userFromToken($token);
        } catch (Exception $e) {
            Log::error("Social auth failed for [{$provider}]: {$e->getMessage()}", [
                'provider' => $provider,
                'exception' => $e,
            ]);

            throw new Exception(
                'Unable to authenticate with the provided token. Please try again.',
                401
            );
        }
    }

    /**
     * Find an existing user or create a new one from the social profile.
     *
     * Account linking strategy:
     * 1. First, look for a user already linked to this provider + provider_id.
     * 2. If not found, look for a user with the same email (link the account).
     * 3. If neither found, create a brand-new user.
     *
     * This ensures seamless account linking when a user who previously
     * registered with email+password later signs in with a social provider.
     */
    protected function findOrCreateUser(\Laravel\Socialite\Two\User $socialUser, string $provider, string $role): User
    {
        return DB::transaction(function () use ($socialUser, $provider, $role) {

            // 1. Check if this social account is already linked
            $user = User::where('provider', $provider)
                ->where('provider_id', $socialUser->getId())
                ->first();

            if ($user) {
                $this->updateLastLogin($user);

                return $user;
            }

            // 2. Check if a user with this email already exists (auto-link)
            $email = $socialUser->getEmail();

            if ($email) {
                $user = User::where('email', $email)->whereNull('deleted_at')->first();

                if ($user) {
                    $this->linkSocialAccount($user, $socialUser, $provider);
                    $this->updateLastLogin($user);

                    return $user;
                }
            }

            // 3. Create a brand-new user from the social profile
            return $this->createUserFromSocialProfile($socialUser, $provider, $role);
        });
    }

    /**
     * Link a social provider account to an existing user.
     *
     * Only updates the social fields. Does NOT overwrite the user's existing
     * name, email, or other profile data — preserving their original identity.
     * The provider avatar is stored as a bonus profile picture option.
     */
    protected function linkSocialAccount(User $user, \Laravel\Socialite\Two\User $socialUser, string $provider): void
    {
        $updateData = [
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
        ];

        // Only set avatar if the user doesn't already have a profile picture
        if (! $user->profile_picture && $socialUser->getAvatar()) {
            $updateData['provider_avatar'] = $socialUser->getAvatar();
        }

        // Mark email as verified since the social provider already verified it
        if (! $user->email_verified_at) {
            $updateData['email_verified_at'] = now();
        }

        // Activate the account if it was inactive
        if (! $user->is_active) {
            $updateData['is_active'] = true;
        }

        $user->update($updateData);
    }

    /**
     * Create a brand-new user from a social profile.
     *
     * Extracts firstname, lastname, email, and avatar from the provider.
     * Handles Apple's quirk of only providing the name on first sign-in.
     * No password is set — social users authenticate via their provider.
     */
    protected function createUserFromSocialProfile(\Laravel\Socialite\Two\User $socialUser, string $provider, string $role): User
    {
        $names = $this->extractNames($socialUser, $provider);

        $user = User::create([
            'firstname' => $names['firstname'],
            'lastname' => $names['lastname'],
            'email' => $socialUser->getEmail(),
            'password' => null,
            'role' => $this->resolveRole($role),
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'provider_avatar' => $socialUser->getAvatar(),
            'referral_code' => Str::random(10),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        // Create a wallet for the new user (consistent with regular registration)
        Wallet::create(['user_id' => $user->id]);

        return $user;
    }

    /**
     * Extract first and last name from the social user profile.
     *
     * Social providers return names in different formats:
     * - Google/Facebook: Full name, sometimes split into first/last.
     * - Apple: Only provides name on the FIRST sign-in, then returns null.
     *
     * Falls back to the email prefix if no name is available.
     */
    protected function extractNames(\Laravel\Socialite\Two\User $socialUser, string $provider): array
    {
        $name = $socialUser->getName();
        $email = $socialUser->getEmail();

        // If no name is provided (common with Apple after first login),
        // derive a reasonable default from the email address.
        if (empty($name)) {
            $emailPrefix = $email ? Str::before($email, '@') : 'User';
            $name = Str::title(str_replace(['.', '_', '-'], ' ', $emailPrefix));
        }

        // Split the full name into first and last.
        // If only one word, use it as firstname with an empty lastname.
        $parts = explode(' ', $name, 2);

        return [
            'firstname' => trim($parts[0]),
            'lastname' => isset($parts[1]) ? trim($parts[1]) : '',
        ];
    }

    /**
     * Resolve and validate the requested role.
     *
     * Only allows 'customer' and 'vendor' for social sign-ups.
     * Admin roles are never assignable through social authentication.
     */
    protected function resolveRole(string $role): string
    {
        $allowedRoles = [
            UserPermissionsEnum::CUSTOMER(),
            UserPermissionsEnum::VENDOR(),
        ];

        if (in_array($role, $allowedRoles)) {
            return $role;
        }

        // Default to customer if an invalid role is provided
        return UserPermissionsEnum::CUSTOMER();
    }

    /**
     * Update the user's last login timestamp.
     */
    protected function updateLastLogin(User $user): void
    {
        $user->update(['last_login' => now()]);
    }

    /**
     * Issue a Sanctum bearer token for the authenticated user.
     *
     * Returns the user profile merged with the token, consistent
     * with the existing LoginService response format.
     */
    protected function issueToken(User $user): array
    {
        $token = $user->createToken('social_auth_token')->plainTextToken;

        return array_merge(
            (new UserResource($user))->toArray(request()),
            [
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]
        );
    }
}
