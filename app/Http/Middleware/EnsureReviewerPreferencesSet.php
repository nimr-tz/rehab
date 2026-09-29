<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureReviewerPreferencesSet
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Only check if user is a reviewer
        if ($user && $user->hasRole('reviewer')) {
            
            // Skip check if already on the preferences page or trying to save preferences
            if ($request->routeIs('reviewer.preferences.*')) {
                return $next($request);
            }

            if (!$user->reviewer_preferences_set) {
                return redirect()->route('reviewer.preferences.index')
                    ->with('warning', 'Please set your reviewing preferences and expertise areas before continuing.');
            }
        }

        return $next($request);
    }
}
