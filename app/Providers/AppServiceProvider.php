<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Throttle the anonymous public API per client IP address.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('public-api', function (Request $request): Limit {
            return Limit::perMinute(config()->integer('pintu.public_api.requests_per_minute'))
                ->by($request->ip())
                ->response(fn (Request $request, array $headers): JsonResponse => response()->json([
                    'message' => 'Terlalu banyak permintaan. Silakan coba lagi sebentar lagi.',
                ], 429, $headers));
        });
    }
}
