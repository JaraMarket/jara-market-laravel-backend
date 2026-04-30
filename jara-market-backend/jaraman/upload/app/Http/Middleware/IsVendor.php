<?php

namespace App\Http\Middleware;

use App\Enums\UserPermissionsEnum;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class IsVendor
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (! auth()->check() || ! in_array(auth()->user()->role, [UserPermissionsEnum::VENDOR(), UserPermissionsEnum::ADMIN()])) {
            abort(403, 'Unauthorized action');
        }

        $request->merge(['is_vendor' => true]);

        return $next($request);
    }
}
