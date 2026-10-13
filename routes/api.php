<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\PublicFaqController;
use App\Http\Middleware\EnsureAccountIsActive;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

Route::get('/health', function (): JsonResponse {
    return response()->json([
        'status' => 'ok',
    ]);
});

/*
 * Employee sign-in with a cookie session (Sanctum SPA authentication). The
 * login page first calls GET /sanctum/csrf-cookie and then sends the
 * X-XSRF-TOKEN header with every state-changing request. Inactive accounts
 * are signed out by EnsureAccountIsActive, which runs on the whole api group.
 */
Route::post('/masuk', [AuthenticatedSessionController::class, 'store'])
    ->middleware('throttle:login')
    ->name('auth.masuk');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/sesi', [AuthenticatedSessionController::class, 'show'])->name('auth.sesi');
    Route::post('/keluar', [AuthenticatedSessionController::class, 'destroy'])->name('auth.keluar');
});

/*
 * Anonymous, read-only content for the public portal. No session is started,
 * so visitors never receive a cookie from these endpoints.
 */
Route::prefix('public')
    ->name('public.')
    ->middleware('throttle:public-api')
    ->withoutMiddleware([EnsureFrontendRequestsAreStateful::class, EnsureAccountIsActive::class])
    ->group(function (): void {
        Route::get('/faq', PublicFaqController::class)->name('faq.index');
    });
