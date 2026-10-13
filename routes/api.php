<?php

use App\Http\Controllers\PublicFaqController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::get('/health', function (): JsonResponse {
    return response()->json([
        'status' => 'ok',
    ]);
});

Route::prefix('public')
    ->name('public.')
    ->middleware('throttle:public-api')
    ->group(function (): void {
        Route::get('/faq', PublicFaqController::class)->name('faq.index');
    });
