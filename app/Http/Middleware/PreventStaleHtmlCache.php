<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventStaleHtmlCache
{
    /**
     * Stop HTML documents from being cached by the browser (incl. the back/forward
     * "bfcache"), proxies, or a CDN.
     *
     * Every rendered page embeds a per-session CSRF token. If a cached copy is
     * replayed, the visitor is handed a stale token whose session no longer matches,
     * producing a 419 "Page Expired" on the next POST — the classic cause of
     * seemingly random 419s right after login across the whole system.
     *
     * Only HTML responses are touched; assets, JSON APIs, and file downloads keep
     * their own caching behaviour.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $contentType = $response->headers->get('Content-Type', '');

        if (str_contains($contentType, 'text/html')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }
}
