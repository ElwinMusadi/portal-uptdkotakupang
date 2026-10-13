<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    /**
     * End the session of an account that is no longer active, so suspending
     * or deactivating it takes effect on the very next request. Registered on
     * the whole web and api groups, so new routes cannot forget it.
     *
     * @param  Closure(Request): (Response)  $next
     *
     * @throws AuthenticationException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ! $user->canSignIn()) {
            Log::warning('auth.inactive', ['user' => $user->getKey(), 'ip' => $request->ip()]);

            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            throw new AuthenticationException('Akun Anda tidak aktif.');
        }

        return $next($request);
    }
}
