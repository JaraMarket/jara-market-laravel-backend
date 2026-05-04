<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;

class VerificationController extends Controller
{
    public function verify(Request $request)
    {
        $user = User::findOrFail($request->route('id'));

        if (!hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            return response()->json(['message' => 'Invalid verification link.'], 403);
        }

        if ($user->hasVerifiedEmail()) {
            // Already verified, just redirect
            return redirect()->to('jaramarket://auth/verified?status=already_verified');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            $user->update(['is_active' => 1]);
        }

        // Redirect to the Flutter App via Deep Link
        return redirect()->to('jaramarket://auth/verified?status=success');
    }
}
