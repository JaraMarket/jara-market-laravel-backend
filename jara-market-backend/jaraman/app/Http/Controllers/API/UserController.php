<?php

namespace App\Http\Controllers\API;

use OpenApi\Attributes as OA;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\OtpRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResendOtpRequest;
use App\Http\Requests\UserProfileRequest;
use App\Http\Resources\ReferralResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\WalletResource;
use App\Models\User;
use App\Services\LoginService;
use App\Services\UserRegistrationService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserController extends Controller
{
    public function __construct(public UserRegistrationService $userService, public LoginService $loginService) {}

    #[OA\Post(
        path: "/api/auth/register",
        summary: "Register a new user",
        description: "Creates a new user account (Customer or Vendor). A verification OTP will be sent to the email address.",
        tags: ["Customer Authentication", "Vendor Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["firstname", "lastname", "email", "password", "role"],
                properties: [
                    new OA\Property(property: "firstname", type: "string", example: "John"),
                    new OA\Property(property: "lastname", type: "string", example: "Doe"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "user@example.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "Password123!"),
                    new OA\Property(property: "password_confirmation", type: "string", format: "password", example: "Password123!"),
                    new OA\Property(property: "role", type: "string", enum: ["customer", "vendor"], example: "customer"),
                    new OA\Property(property: "phone_number", type: "string", example: "08012345678"),
                    new OA\Property(property: "referral_code", type: "string", example: "REF123")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Success - OTP sent to email",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "An OTP has been sent to your email address."),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation Error"),
            new OA\Response(response: 500, description: "Server Error")
        ]
    )]
    public function registerUser(RegisterRequest $request)
    {
        // dd('LIVE_CODE_V1');
        try {
            $user = $this->userService->register($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'An OTP has been sent to your email address. It expires after 15 minutes.',
                'data' => new UserResource($user),
            ], 201);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    #[OA\Post(
        path: "/jaram/resend-otp",
        summary: "Resend verification OTP",
        description: "Resends a new 6-digit OTP to the user's email address.",
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
            new OA\Response(response: 201, description: "OTP Resent"),
            new OA\Response(response: 422, description: "Validation Error")
        ]
    )]
    public function resendOtp(ResendOtpRequest $request)
    {
        try {
            $this->userService->sendOtp($request->email);

            return response()->json([
                'status' => true,
                'message' => 'An OTP has been sent to your email address. It expires after 15 minutes.',
                'data' => null,
            ], 201);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    #[OA\Post(
        path: "/jaram/validate-otp",
        summary: "Verify email with OTP",
        description: "Validates the 6-digit OTP sent to the user's email. On success, returns a Sanctum Bearer token for immediate login.",
        tags: ["Customer Authentication", "Vendor Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "otp"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "user@example.com"),
                    new OA\Property(property: "otp", type: "string", example: "123456")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Email verified & Token generated",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "OTP validated successfully"),
                        new OA\Property(property: "data", type: "object", properties: [
                            new OA\Property(property: "token", type: "string", example: "1|abcdef123456..."),
                            new OA\Property(property: "user", type: "object")
                        ])
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Invalid or expired OTP")
        ]
    )]
    public function validateUserRegisterOTP(OtpRequest $request)
    {
        try {
            $data = $request->validated();
            $user = $this->userService->validateOTP($data['email'], $data['otp']);

            // Mark email as verified and active
            $this->userService->validateEmail($user);

            // Generate token for immediate login
            $authData = $this->loginService->mergeUserWithToken($user);

            return response()->json([
                'status' => true,
                'message' => 'OTP validated successfully and email verified',
                'data' => $authData,
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 400);
        }
    }

    public function verifyEmailWithOTP(OtpRequest $request)
    {
        $user = $this->userService->validateOTP($request->email, $request->otp);

        return $this->validateEmail($user);
    }

    public function validateEmail(User $user)
    {
        try {

            $this->userService->validateEmail($user);

            return response()->json([
                'status' => true,
                'message' => 'Email verified successfully and registration complete',
                'data' => new UserResource($user),
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 400);
        }
    }

    #[OA\Post(
        path: "/api/auth/login",
        summary: "Login user",
        description: "Authenticates a user and returns a Sanctum Bearer token.",
        tags: ["Customer Authentication", "Vendor Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "user@example.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "Password123!")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Login successful",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "User Authenticated successfully"),
                        new OA\Property(property: "data", type: "object", properties: [
                            new OA\Property(property: "token", type: "string", example: "2|xyz789..."),
                            new OA\Property(property: "user", type: "object")
                        ])
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Invalid credentials"),
            new OA\Response(response: 403, description: "Account not verified")
        ]
    )]
    public function login(LoginRequest $request)
    {
        try {
            $data = $this->loginService->loginUser($request);

            return response()->json([
                'status' => true,
                'message' => 'User Authenticated successfully',
                'data' => $data,
            ], 201);
        } catch (Exception $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' => 'User Authenticated failed',
                'data' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Post(
        path: "/api/auth/logout",
        summary: "Logout user",
        description: "Revokes the current access token.",
        tags: ["Customer Authentication", "Vendor Authentication"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Logged out successfully"),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function logout(Request $request)
    {
        if ($request->user()) {
            // Revoke current token
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'status' => true,
                'message' => 'Session ended! Logout was successful.',
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'Not authenticated.',
        ], 401);
    }

    public function fetchUserProfile()
    {
        try {
            $user = auth()->user();

            return response()->json([
                'status' => true,
                'message' => 'User Profile retrieved successfully',
                'data' => new UserResource($user),
            ], 201);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'User Profile not found'], 404);
        }
    }

    public function myRefferals()
    {
        try {
            $user = auth()->user();

            return response()->json([
                'status' => true,
                'message' => 'Refferals retrieved successfully',
                'data' => ReferralResource::collection($user->referrals()->latest()->get()),
            ], 201);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'User refferals not found'], 404);
        }
    }

    public function fetchUserWallet()
    {
        try {
            $user = auth()->user();

            return response()->json([
                'status' => true,
                'message' => 'User Wallet retrieved successfully',
                'data' => new WalletResource($user->wallet),
            ], 201);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'User Profile not found'], 404);
        }
    }

    public function editUserProfile(UserProfileRequest $request)
    {
        try {
            $user = $this->userService->updateProfile($request);

            return response()->json(['status' => true, 'message' => 'Profile Updated Successfully', 'data' => new UserResource($user)], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Problem Updating Profile'], 400);
        }
    }

    public function updateProfile(UserProfileRequest $request, $email)
    {
        try {
            $user = $this->userService->updateProfile($request, $email);

            return response()->json(['status' => true, 'message' => 'Profile Updated Successfully', 'data' => new UserResource($user)], 201);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Problem Updating Profile'], 400);
        }
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        try {
            $user = auth()->user();
            $user->update(['password' => $request->password]);

            return response()->json(['status' => true, 'message' => 'Password changed successfully', 'data' => new UserResource($user)], 201);
        } catch (Exception $e) {
            report($e);

            return response()->json(['status' => false, 'message' => 'Password change failed! Please contact admin', 'data' => null], 201);
        }
    }

}
