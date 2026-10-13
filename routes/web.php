<?php

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
 * Public portal pages from resources/views/portal, served at clean URLs. The
 * former static-site addresses (/profil.html, ...) redirect permanently and
 * keep their query string, which the login page reads (?lanjut=, ?sesi=).
 */
$portalPages = [
    'profil',
    'visi-misi',
    'struktur-organisasi',
    'layanan',
    'jadwal',
    'informasi',
    'berita-detail',
    'dokumentasi',
    'unduhan',
    'kontak',
    'login',
    'dashboard-pegawai',
    'design-system',
    'kebijakan-privasi',
    'syarat-ketentuan',
];

Route::view('/', 'portal.index')->name('portal.index');

foreach ($portalPages as $page) {
    Route::view("/{$page}", "portal.{$page}")->name("portal.{$page}");
}

Route::get('/{page}.html', function (Request $request, string $page): RedirectResponse {
    $path = $page === 'index' ? '/' : "/{$page}";
    $query = $request->server('QUERY_STRING');

    return redirect(filled($query) ? "{$path}?{$query}" : $path, 301);
})->whereIn('page', ['index', ...$portalPages]);

/*
 * Content management dashboard (React, resources/js/dashboard). It uses hash
 * routing (/dashboard/#/berita), so this single route serves every screen.
 */
Route::view('/dashboard', 'dashboard')->name('dashboard');
