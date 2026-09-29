<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FinanceOfficerMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // Allow admins or finance officers
        if (!auth()->user()->hasAnyRole(['admin', 'finance_officer'])) {
            abort(403, 'Access denied. Finance Officer privileges required.');
        }

        return $next($request);
    }
}
