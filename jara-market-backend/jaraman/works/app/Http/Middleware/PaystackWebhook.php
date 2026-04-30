<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PaystackWebhook
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (app()->environment('staging', 'production')) {
            $input = @file_get_contents('php://input');
            define('PAYSTACK_SECRET_KEY', config('app.paystack_secret_key'));

            // Validate event all at once to avoid timing attack
            if ($request->header('X-PAYSTACK-SIGNATURE') !== hash_hmac('sha512', $input, PAYSTACK_SECRET_KEY)) {
                Log::alert('Paystack webhook called with invalid signature', $request->all());

                return response()->errorResponse("You're not authorized to access this resource.", Response::HTTP_FORBIDDEN);
            }
        }

        return $next($request);
    }
}
