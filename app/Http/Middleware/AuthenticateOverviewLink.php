<?php

namespace App\Http\Middleware;

use App\Models\OverviewLink;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets through only a request holding the token of a link to the Ikhtisar
 * page (D-099) as its bearer token, before anything else reads the request:
 * without one, every request gets the same 401, whatever it asks. The time
 * the link was last opened is kept to the minute. The token is never read
 * from the address and never logged.
 */
class AuthenticateOverviewLink
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $link = $token === null ? null : OverviewLink::query()->where('token_hash', OverviewLink::hash($token))->first();

        if ($link === null) {
            return response()->json(['message' => 'Tautan ini tidak berlaku atau sudah dicabut.'], 401);
        }

        if ($link->terakhir_dibuka === null || $link->terakhir_dibuka->lt(now()->subMinute())) {
            $link->forceFill(['terakhir_dibuka' => now()])->save();
        }

        return $next($request);
    }
}
