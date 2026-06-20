<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Closed system: the whole site must stay out of search engines.
 * Sends X-Robots-Tag on every response (covers non-HTML too), alongside
 * the meta robots tag in the layout and robots.txt.
 */
class PreventIndexing
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
