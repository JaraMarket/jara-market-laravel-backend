<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;
use App\Http\Requests\ForgotPasswordLinkRequest;
use App\Models\User;
use App\Services\UserRegistrationService;
use Exception;

class ForgotPasswordController extends Controller
{
    public function __construct(public UserRegistrationService $userService) {}

    #[OA\Post(
        path: "/api/auth/forgot-password",
        summary: "Request Password Reset OTP",
        description: "Sends a 6-digit OTP to the user's email address to initiate password reset.",
        tags: ["Customer Authentication", "Vendor Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "user@example.com")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "OTP Sent"),
            new OA\Response(response: 500, description: "User not found or inactive")
        ]
    )]
    public function sendResetLink(ForgotPasswordLinkRequest $request)
    {
        try {
            $user = User::where('email', $request->email)->first();

            if (! $user || ! $user->is_active) {
                return response()->json(['status' => false, 'message' => 'User account not found or in active'], 500);
            }

            $this->userService->sendOtp($user->email);

            return response()->json([
                'status' => true,
                'message' => 'An OTP has been sent to your email address. It expires after 15 minutes.',
                'data' => [],
            ], 201);
        } catch (Exception $e) {
            report($e);

            return response()->json([
                'status' => true,
                'message' => $e->getMessage(),
                'data' => [],
            ], 201);
        }
    }
}
