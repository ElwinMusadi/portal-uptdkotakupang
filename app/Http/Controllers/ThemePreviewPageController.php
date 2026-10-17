<?php

namespace App\Http\Controllers;

use App\Support\ThemePreview;
use Illuminate\Http\RedirectResponse;

class ThemePreviewPageController extends Controller
{
    /**
     * Open a portal preview link from Pengaturan › Tampilan (D-057): the
     * browser keeps the token in a cookie, so /tema-portal.css serves it the
     * unsaved theme on every portal page, and lands on the homepage. An
     * expired link only lands there.
     */
    public function show(string $token): RedirectResponse
    {
        $response = redirect()->route('portal.index');

        return ThemePreview::find($token) === null
            ? $response->withoutCookie(ThemePreview::COOKIE)
            : $response->withCookie(cookie(ThemePreview::COOKIE, $token, ThemePreview::MINUTES));
    }

    /**
     * End the preview in this browser and land on the homepage with the
     * saved theme.
     */
    public function destroy(): RedirectResponse
    {
        return redirect()->route('portal.index')->withoutCookie(ThemePreview::COOKIE);
    }
}
