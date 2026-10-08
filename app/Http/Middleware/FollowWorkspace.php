<?php

namespace App\Http\Middleware;

use App\Support\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Keeps the sidebar in step with the screen: opening another role's screen moves the person into that role. */
class FollowWorkspace
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET')) {
            Workspace::follow($request);
        }

        return $next($request);
    }
}
