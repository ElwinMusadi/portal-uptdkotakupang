<?php

use App\Http\Controllers\ArticlePageController;
use App\Http\Controllers\Auth\AccountActivationController;
use App\Http\Controllers\MessageTrackingPageController;
use App\Http\Controllers\QueueTrackingPageController;
use App\Http\Controllers\ThemePreviewPageController;
use App\Http\Controllers\ThemeStylesheetController;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Models\Article;
use App\Models\Message;
use App\Models\QueueTicket;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
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
    'aplikasi',
    'jadwal',
    'informasi',
    'dokumentasi',
    'unduhan',
    'penerimaan',
    'pemetaan',
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

    // The taxpayer's tracking page behind a queue ticket's QR (Antrean Online, D-051)
    Route::get('/lacak/{kode}', QueueTrackingPageController::class)->where('kode', QueueTicket::TRACKING_CODE_PATTERN)->name('portal.lacak');
    // The sender's tracking page for a message to Pesan & Pengaduan (D-058)
    Route::get('/pesan/{kode}', MessageTrackingPageController::class)->where('kode', Message::TRACKING_CODE_PATTERN)->name('portal.lacak-pesan');
    // The waiting-room TV display; it reads GET /api/layar with the token of its setup link (D-051)
    Route::view('/layar', 'layar')->name('layar');

    // Portal scripts that Vite builds, loaded by the pages as written from fixed addresses: the tax check on Beranda and
    // Layanan (D-041) and the React components that take over [data-react] elements (D-045).
    Route::get('/skrip/cek-pajak', fn (): RedirectResponse => redirect(Vite::asset('resources/js/portal/cek-pajak.ts')))->name('portal.cek-pajak');
    Route::get('/skrip/portal', fn (): RedirectResponse => redirect(Vite::asset('resources/js/portal/main.tsx')))->name('portal.skrip');

    // The theme stylesheets loaded after the portal's and the dashboard's own, revalidated on every load (D-057)
    Route::middleware('cache.headers:no_cache;etag')->group(function (): void {
        Route::get('/tema-portal.css', [ThemeStylesheetController::class, 'portal'])->name('tema.portal');
        Route::get('/tema-dashboard.css', [ThemeStylesheetController::class, 'dashboard'])->name('tema.dashboard');
    });

    // A portal preview link from Pengaturan › Tampilan: this browser sees the unsaved portal theme for a while (D-057)
    Route::get('/pratinjau-tema/selesai', [ThemePreviewPageController::class, 'destroy'])->name('tema.pratinjau.selesai');
    Route::get('/pratinjau-tema/{token}', [ThemePreviewPageController::class, 'show'])->where('token', '[A-Za-z0-9]{40}')->name('tema.pratinjau');
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
