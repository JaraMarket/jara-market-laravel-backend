<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;
use App\Http\Requests\ResetForgottenPasswordRequest;
use App\Http\Resources\UserResource;
use App\Services\UserRegistrationService;
use Exception;

class ResetPasswordController extends Controller
{
    public function __construct(public UserRegistrationService $userService) {}

    #[OA\Post(
        path: "/api/auth/reset-password",
        summary: "Reset Password",
        description: "Resets the user's password using the OTP and email.",
        tags: ["Customer Authentication", "Vendor Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "otp", "password", "password_confirmation"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "user@example.com"),
                    new OA\Property(property: "otp", type: "string", example: "123456"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "NewPassword123!"),
                    new OA\Property(property: "password_confirmation", type: "string", format: "password", example: "NewPassword123!")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Password reset successful"),
            new OA\Response(response: 400, description: "Invalid OTP or request")
        ]
    )]
    public function reset(ResetForgottenPasswordRequest $request)
    {
        try {
            $user = $request->user;
            $user->update([
                'password' => $request->password,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Password reset successful',
                'data' => new UserResource($user),
            ], 200);

        } catch (Exception $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Password reset not successful',
                'data' => [],
            ], $e->getCode());
        }
    }
}
