<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets the figures of the Ikhtisar page through only while an administrator
 * has it open (D-100), before anything else reads the request: while it is
 * closed, every request gets the same 403, whatever it asks.
 */
class EnsureOverviewIsOpen
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Setting::overview()['aktif']) {
            return response()->json(['message' => 'Halaman ini sedang dinonaktifkan oleh Administrator PINTU.'], 403);
        }

        return $next($request);
    }
}
