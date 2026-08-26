<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->is_active) {
            if ($request->bearerToken()) {
                $request->user()->currentAccessToken()?->delete();
            }
            auth()->guard('web')->logout();

            return response()->json(['message' => 'This account is unavailable. Please contact the practice.'], 403);
        }

        return $next($request);
    }
}
