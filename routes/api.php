<?php

use App\Http\Controllers\Admin\AccountActivationLinkController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\AccountStatusController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\PublicFaqController;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Models\User;
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

    /*
     * Account management for administrators (UserPolicy). Passwords are
     * never set here: the owner chooses one through an activation link.
     */
    Route::prefix('akun')->name('akun.')->whereUlid('user')->group(function (): void {
        Route::get('/', [AccountController::class, 'index'])->can('viewAny', User::class)->name('index');
        Route::post('/', [AccountController::class, 'store'])->can('create', User::class)->name('store');
        Route::patch('/{user}', [AccountController::class, 'update'])->can('update', 'user')->name('update');
        Route::put('/{user}/status', [AccountStatusController::class, 'update'])->can('changeStatus', 'user')->name('status');
        Route::post('/{user}/tautan-aktivasi', [AccountActivationLinkController::class, 'store'])
            ->can('issueActivationLink', 'user')
            ->name('tautan-aktivasi');
    });
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
