<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RegistrationOfficerMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $isApiRequest = $request->expectsJson() || $request->is('api/*');

        if (!$user) {
            if ($isApiRequest) {
                return response()->json([
                    'message' => 'Authentication required.',
                ], 401);
            }

            return redirect()->route('login');
        }

        // Allow admins or registration officers
        if (!$user->hasAnyRole(['admin', 'registration_officer'])) {
            if ($isApiRequest) {
                return response()->json([
                    'message' => 'Registration officer privileges required.',
                ], 403);
            }

            abort(403, 'Access denied. Registration Officer privileges required.');
        }

        return $next($request);
    }
}
