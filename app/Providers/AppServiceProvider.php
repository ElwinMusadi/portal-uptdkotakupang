<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\Sanctum;

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
        $this->configureAuthentication();

        // The API answers with bare JSON (docs/api.md), resources included.
        JsonResource::withoutWrapping();
    }

    /**
     * Throttle the anonymous public API and sign-in attempts per client IP
     * address. Sign-in also has per-NIP limits in LoginRequest.
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

        // The portal's revenue report (D-048) sums the transactions for every request: 60 per minute from one address.
        RateLimiter::for('public-penerimaan', function (Request $request): Limit {
            return Limit::perMinute(60)
                ->by($request->ip())
                ->response(fn (Request $request, array $headers): JsonResponse => response()->json([
                    'message' => 'Terlalu banyak permintaan. Silakan coba lagi sebentar lagi.',
                ], 429, $headers));
        });

        // The portal's contact form (D-016): at most 5 messages per hour from one address.
        RateLimiter::for('public-messages', function (Request $request): Limit {
            return Limit::perHour(5)
                ->by($request->ip())
                ->response(fn (Request $request, array $headers): JsonResponse => response()->json([
                    'message' => 'Terlalu banyak pesan dari jaringan ini. Coba lagi dalam satu jam, atau hubungi kami lewat kanal lain.',
                ], 429, $headers));
        });

        // Looking up a message by ticket number and contact address (D-058): 5 a minute and 30 an hour from one address.
        RateLimiter::for('public-message-lookups', function (Request $request): array {
            $tooMany = fn (Request $request, array $headers): JsonResponse => response()->json([
                'message' => 'Terlalu banyak percobaan dari jaringan ini. Coba lagi beberapa saat lagi.',
            ], 429, $headers);

            return [
                Limit::perMinute(5)->by('menit:'.$request->ip())->response($tooMany),
                Limit::perHour(30)->by('jam:'.$request->ip())->response($tooMany),
            ];
        });

        RateLimiter::for('login', function (Request $request): Limit {
            return Limit::perMinute(30)
                ->by($request->ip())
                ->response(fn (Request $request, array $headers): JsonResponse => response()->json([
                    'message' => 'Terlalu banyak percobaan masuk dari jaringan ini. Coba lagi sebentar lagi.',
                ], 429, $headers));
        });

        // Vehicle lookups through the private NJKB API (D-041), which allows PINTU 60 calls a
        // minute: 30 a minute for each employee, and for the portal 5 a minute and 20 an hour
        // from one address, at most 30 a minute from all visitors together.
        RateLimiter::for('kendaraan', function (Request $request): Limit {
            return Limit::perMinute(30)
                ->by($request->user()?->getAuthIdentifier() ?? $request->ip())
                ->response(fn (Request $request, array $headers): JsonResponse => response()->json([
                    'message' => 'Terlalu banyak pencarian kendaraan. Coba lagi sebentar lagi.',
                ], 429, $headers));
        });
        RateLimiter::for('public-kendaraan', function (Request $request): array {
            $tooMany = fn (Request $request, array $headers): JsonResponse => response()->json([
                'message' => 'Terlalu banyak pengecekan dari jaringan ini. Coba lagi beberapa saat lagi.',
            ], 429, $headers);

            return [
                Limit::perMinute(5)->by('menit:'.$request->ip())->response($tooMany),
                Limit::perHour(20)->by('jam:'.$request->ip())->response($tooMany),
                Limit::perMinute(30)->by('portal')->response($tooMany),
            ];
        });

        // Employee searches and reads in SIKAP (D-075), which allows PINTU's key 60 calls a
        // minute: 30 a minute for each administrator.
        RateLimiter::for('sikap', function (Request $request): Limit {
            return Limit::perMinute(30)
                ->by($request->user()?->getAuthIdentifier() ?? $request->ip())
                ->response(fn (Request $request, array $headers): JsonResponse => response()->json([
                    'message' => 'Terlalu banyak pencarian pegawai SIKAP. Coba lagi sebentar lagi.',
                ], 429, $headers));
        });

        // Dashboard uploads (D-028): at most 30 files per minute for each employee.
        RateLimiter::for('uploads', function (Request $request): Limit {
            return Limit::perMinute(30)
                ->by($request->user()?->getAuthIdentifier() ?? $request->ip())
                ->response(fn (Request $request, array $headers): JsonResponse => response()->json([
                    'message' => 'Terlalu banyak unggahan. Coba lagi sebentar lagi.',
                ], 429, $headers));
        });
    }

    /**
     * Password policy for employee accounts, cookie-only Sanctum, and an
     * authentication trail in the application log (never with passwords).
     */
    protected function configureAuthentication(): void
    {
        Password::defaults(fn (): Password => Password::min(12)->letters()->numbers());

        // Only cookie sessions are used (D-007): ignore bearer tokens entirely.
        Sanctum::getAccessTokenFromRequestUsing(fn (Request $request): ?string => null);

        Event::listen(function (Login $event): void {
            // An inactive account signed in by its remember cookie is signed out
            // again by EnsureAccountIsActive, so it gets no login stamp or log.
            if ($event->user instanceof User && ! $event->user->canSignIn()) {
                return;
            }

            $request = request();

            if ($request->hasSession()) {
                $request->session()->put('pintu.masuk', now()->toIso8601String());
            }

            Log::info('auth.login', [
                'user' => $event->user->getAuthIdentifier(),
                'remember' => $event->remember,
                'ip' => $request->ip(),
            ]);
        });

        Event::listen(function (Logout $event): void {
            Log::info('auth.logout', ['user' => $event->user?->getAuthIdentifier()]);
        });

        Event::listen(function (Lockout $event): void {
            Log::warning('auth.lockout', [
                'nip' => $event->request->input('nip'),
                'ip' => $event->request->ip(),
            ]);
        });
    }
}
