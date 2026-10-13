<?php

use App\Exceptions\PublicContentUnavailableException;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\PreventResponseCaching;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Cookie sessions for API calls from the portal and dashboard (Sanctum SPA)
        $middleware->statefulApi();
        $middleware->api(prepend: [PreventResponseCaching::class]);

        // Suspending or deactivating an account ends its session on the next request
        $middleware->web(append: [EnsureAccountIsActive::class]);
        $middleware->api(append: [EnsureAccountIsActive::class]);

        // Passwords are compared exactly as typed
        $middleware->trimStrings(except: ['sandi', 'sandi_ulang']);

        $middleware->redirectGuestsTo('/login?lanjut='.rawurlencode('dashboard/'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e): bool {
            return $request->is('api', 'api/*') || $request->expectsJson();
        });

        // Laravel only withholds fields named "password" from the old input it
        // stores in the session after a failed validation.
        $exceptions->dontFlash(['sandi', 'sandi_ulang']);

        // Anonymous traffic must not flood the log while a public source is down.
        $exceptions->throttle(function (Throwable $e): ?Limit {
            return $e instanceof PublicContentUnavailableException
                ? Limit::perMinute(1)->by($e->getMessage())
                : null;
        });
    })->create();
