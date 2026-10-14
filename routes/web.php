<?php

use App\Http\Controllers\ArticlePageController;
use App\Http\Controllers\Auth\AccountActivationController;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Models\Article;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/*
 * Public portal pages from resources/views/portal, served at clean URLs. The
 * former static-site addresses (/profil.html, ...) redirect permanently and
 * keep their query string, which the login page reads (?lanjut=, ?sesi=).
 * They need no session: anonymous visitors get no cookie and no session row.
 */
$portalPages = [
    'profil',
    'visi-misi',
    'struktur-organisasi',
    'layanan',
    'jadwal',
    'informasi',
    'dokumentasi',
    'unduhan',
    'kontak',
    'login',
    'dashboard-pegawai',
    'design-system',
    'kebijakan-privasi',
    'syarat-ketentuan',
];

Route::withoutMiddleware([
    StartSession::class,
    ShareErrorsFromSession::class,
    PreventRequestForgery::class,
    EnsureAccountIsActive::class,
])->group(function () use ($portalPages): void {
    Route::view('/', 'portal.index')->name('portal.index');

    foreach ($portalPages as $page) {
        Route::view("/{$page}", "portal.{$page}")->name("portal.{$page}");
    }

    Route::get('/{page}.html', function (Request $request, string $page): RedirectResponse {
        $path = $page === 'index' ? '/' : "/{$page}";
        $query = $request->server('QUERY_STRING');

        return redirect(filled($query) ? "{$path}?{$query}" : $path, 301);
    })->whereIn('page', ['index', ...$portalPages]);

    // One article per address, filled from the public API (D-031). The former example page leads to the news list.
    Route::get('/berita/{slug}', ArticlePageController::class)->where('slug', Article::SLUG_PATTERN)->name('portal.berita');
    Route::permanentRedirect('/berita-detail', '/informasi');
    Route::permanentRedirect('/berita-detail.html', '/informasi');
});

/*
 * Content management dashboard (React, resources/js/dashboard) for signed-in,
 * active accounts; guests are sent to /login. It uses hash routing
 * (/dashboard/#/berita), so this single route serves every screen.
 */
Route::view('/dashboard', 'dashboard')->middleware('auth')->name('dashboard');

/*
 * Absensi Lapangan for officers (React, resources/js/absen): an installable
 * web app for clocking in at field activities (D-034). Guests are sent to
 * /login and come back here.
 */
Route::view('/absen', 'absen')->middleware('auth')->name('absen');

/*
 * One-time activation links from `php artisan pintu:create-admin`: the new
 * administrator chooses a password and the account becomes active.
 */
Route::get('/aktivasi/{user}', [AccountActivationController::class, 'show'])->name('aktivasi.show');
Route::post('/aktivasi/{user}', [AccountActivationController::class, 'store'])->name('aktivasi.store');
