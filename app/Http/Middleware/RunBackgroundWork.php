<?php

namespace App\Http\Middleware;

use App\Support\BackgroundWork;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Runs due background work after the page has been sent, so nobody waits for
 * it. At most once every few minutes, however busy the portal is.
 */
class RunBackgroundWork
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! config('background.after_requests')) {
            return;
        }

        try {
            if (Cache::add('background:tick', true, config('background.every_seconds'))) {
                app(BackgroundWork::class)->run();
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
