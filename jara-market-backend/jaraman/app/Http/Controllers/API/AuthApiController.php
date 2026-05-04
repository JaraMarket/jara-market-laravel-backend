<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AuthApiController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | GET /api/auth/me
    |--------------------------------------------------------------------------
    */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->load(['state', 'lga', 'wallet']);

            return response()->json([
                'status'  => true,
                'message' => 'User profile retrieved successfully',
                'data'    => [
                    'id'              => $user->id,
                    'firstname'       => $user->firstname,
                    'lastname'        => $user->lastname,
                    'name'            => $user->name,
                    'email'           => $user->email,
                    'phone_number'    => $user->phone_number,
                    'role'            => $user->role,
                    'profile_picture' => $user->profile_picture,
                    'is_active'       => $user->is_active,
                    'is_verified'     => $user->is_verified ?? false,
                    'state'           => $user->state?->name,
                    'lga'             => $user->lga?->name,
                    'wallet_balance'  => $user->wallet ? round((float) $user->wallet->balance, 2) : 0.00,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at'      => $user->created_at,
                ],
            ], 200);
        } catch (Exception $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Failed to retrieve profile'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PUT /api/auth/update-profile
    |--------------------------------------------------------------------------
    */
    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'firstname'    => 'sometimes|string|max:100',
            'lastname'     => 'sometimes|string|max:100',
            'phone_number' => 'sometimes|string|max:20',
            'state_id'     => 'sometimes|integer|exists:states,id',
            'lga_id'       => 'sometimes|integer|exists:lgas,id',
        ]);

        try {
            $user = $request->user();
            $user->update($validated);

            return response()->json([
                'status'  => true,
                'message' => 'Profile updated successfully',
                'data'    => [
                    'id'           => $user->id,
                    'firstname'    => $user->firstname,
                    'lastname'     => $user->lastname,
                    'phone_number' => $user->phone_number,
                ],
            ], 200);
        } catch (Exception $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Profile update failed'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/auth/upload-avatar
    |--------------------------------------------------------------------------
    */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        try {
            $path = Storage::disk('s3')->put('avatars', $request->file('avatar'), 'public');
            $url  = Storage::disk('s3')->url($path);

            $request->user()->update(['profile_picture' => $url]);

            return response()->json([
                'status'  => true,
                'message' => 'Avatar uploaded successfully',
                'data'    => ['url' => $url],
            ], 200);
        } catch (Exception $e) {
            report($e);
            return response()->json([
                'status'  => false,
                'message' => 'Avatar upload failed: ' . $e->getMessage(),
                'data'    => [],
            ], 500);
        }
    }
}
