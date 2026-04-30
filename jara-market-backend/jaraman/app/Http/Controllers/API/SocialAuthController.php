<?php

namespace App\Http\Controllers\API;

use App\Enums\UserPermissionsEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\SocialLoginRequest;
use App\Services\SocialAuthService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SocialAuthController extends Controller
{
    public function __construct(
        protected SocialAuthService $socialAuthService
    ) {}

    /**
     * Authenticate a user via a social provider.
     *
     * The mobile app handles the OAuth flow natively (e.g., Google Sign-In,
     * Sign in with Apple, Facebook Login), obtains an access/ID token from
     * the provider, and sends it here for server-side verification.
     *
     * Endpoint: POST /api/jaram/social/{provider}
     *
     * @param  SocialLoginRequest  $request
     * @param  string  $provider  The OAuth provider (google, facebook, apple).
     * @return JsonResponse
     */
    public function authenticate(SocialLoginRequest $request, string $provider): JsonResponse
    {
        try {
            $data = $this->socialAuthService->authenticate(
                provider: $provider,
                token: $request->validated('token'),
                role: $request->validated('role', UserPermissionsEnum::CUSTOMER()),
            );

            return response()->json([
                'status' => true,
                'message' => 'Authentication successful.',
                'data' => $data,
            ], 200);

        } catch (Exception $e) {
            Log::error("Social authentication failed [{$provider}]: {$e->getMessage()}");

            $statusCode = $this->resolveHttpStatus($e->getCode());

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * Map exception codes to valid HTTP status codes.
     */
    protected function resolveHttpStatus(int $code): int
    {
        return match (true) {
            $code >= 400 && $code < 600 => $code,
            default => 500,
        };
    }
}
