<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Restrict a route to users holding any of the given roles (admins always pass).
     *
     * Usage: ->middleware('role:chief_rapporteur') or ->middleware('role:chair,rapporteur')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $allowed = array_unique(array_merge(['admin'], $roles));

        if (! auth()->user()->hasAnyRole($allowed)) {
            abort(403, 'Access denied. You do not have permission to view this page.');
        }

        return $next($request);
    }
}
