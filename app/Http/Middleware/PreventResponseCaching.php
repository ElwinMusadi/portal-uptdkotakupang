<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventResponseCaching
{
    /**
     * Forbid browsers and shared caches from storing any response, errors
     * included, so a stale copy can never hide a change or a withdrawal.
     * Laravel's cache.headers middleware only touches successful responses.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
