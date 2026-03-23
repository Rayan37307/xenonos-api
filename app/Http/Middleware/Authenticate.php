<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

class Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string ...$guards): Response
    {
        // Check for Sanctum token authentication (API)
        if ($request->expectsJson() || $request->is('api/*')) {
            $user = $request->user('sanctum');
            
            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            return $next($request);
        }

        // Check for session authentication (Web)
        if (!$request->session()->has('auth_token')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
