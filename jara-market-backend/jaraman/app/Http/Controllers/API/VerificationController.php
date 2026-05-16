<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;

class VerificationController extends Controller
{
    #[OA\Get(
        path: "/jaram/verify-email/{id}/{hash}",
        summary: "Verify Email (Magic Link)",
        description: "Clickable link sent via email. Validates the hash and redirects the user back to the Flutter app via deep link.",
        tags: ["Customer Authentication", "Vendor Authentication"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "The User ID", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "hash", in: "path", required: true, description: "The Verification Hash", schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 302, description: "Redirects to jaramarket://auth/verified"),
            new OA\Response(response: 403, description: "Invalid hash")
        ]
    )]
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
