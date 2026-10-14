<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class ArticlePageController extends Controller
{
    /**
     * Serve the portal's article page at /berita/{slug}; main.js fills it
     * from GET /api/public/berita/{slug} (D-031).
     */
    public function __invoke(): Response
    {
        return response(self::fromSiteRoot(view('portal.berita-detail')->render()));
    }

    /**
     * Portal pages are written with addresses relative to the site root
     * (assets/…, profil.html), which would resolve under /berita/ on this
     * page, so they are made root-relative. Fragments (#…), root-relative
     * and full addresses stay as they are.
     */
    public static function fromSiteRoot(string $html): string
    {
        return preg_replace('/(?<=\s)(href|src)="(?![a-z][a-z0-9+.-]*:|\/|#)/i', '$1="/', $html);
    }
}
