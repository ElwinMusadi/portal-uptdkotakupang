<?php

use App\Exceptions\PublicContentUnavailableException;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\PreventResponseCaching;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

        // After signing in, officers return to the attendance app, everyone else to the dashboard (D-034)
        $middleware->redirectGuestsTo(fn (Request $request): string => '/login?lanjut='.rawurlencode($request->is('absen') ? 'absen' : 'dashboard/'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e): bool {
            return $request->is('api', 'api/*') || $request->expectsJson();
        });

        // Laravel only withholds fields named "password" from the old input it
        // stores in the session after a failed validation.
        $exceptions->dontFlash(['sandi', 'sandi_ulang']);

        // A record the API cannot find is reported without naming its model class.
        $exceptions->render(function (NotFoundHttpException $e, Request $request): ?JsonResponse {
            return $request->is('api/*') && $e->getPrevious() instanceof ModelNotFoundException
                ? response()->json(['message' => 'Data tidak ditemukan.'], 404)
                : null;
        });

        // Anonymous traffic must not flood the log while a public source is down.
        $exceptions->throttle(function (Throwable $e): ?Limit {
            return $e instanceof PublicContentUnavailableException
                ? Limit::perMinute(1)->by($e->getMessage())
                : null;
        });
    })->create();
