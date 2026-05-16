<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AuthApiController extends Controller
{
    #[OA\Get(
        path: "/api/auth/me",
        summary: "Get My Profile",
        description: "Retrieve the authenticated user's profile details, including wallet balance and location.",
        tags: ["Customer"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Profile retrieved successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 500, description: "Server Error")
        ]
    )]
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

    #[OA\Put(
        path: "/api/auth/update-profile",
        summary: "Update Profile",
        description: "Update user's basic information such as name, phone, and location.",
        tags: ["Customer"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "firstname", type: "string", example: "John"),
                    new OA\Property(property: "lastname", type: "string", example: "Doe"),
                    new OA\Property(property: "phone_number", type: "string", example: "+2348012345678"),
                    new OA\Property(property: "state_id", type: "integer", example: 1),
                    new OA\Property(property: "lga_id", type: "integer", example: 1)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Profile updated successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation Error"),
            new OA\Response(response: 500, description: "Server Error")
        ]
    )]
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

    #[OA\Post(
        path: "/api/auth/upload-avatar",
        summary: "Upload Profile Picture",
        description: "Upload and update the user's profile picture (avatar).",
        tags: ["Customer"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["avatar"],
                    properties: [
                        new OA\Property(property: "avatar", type: "string", format: "binary", description: "Image file (max 2MB)")
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Avatar uploaded successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation Error"),
            new OA\Response(response: 500, description: "Server Error")
        ]
    )]
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
