<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResetForgottenPasswordRequest;
use App\Http\Resources\UserResource;
use App\Services\UserRegistrationService;
use Exception;

class ResetPasswordController extends Controller
{
    public function __construct(public UserRegistrationService $userService) {}

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
