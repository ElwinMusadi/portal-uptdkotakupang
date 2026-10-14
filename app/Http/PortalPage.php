<?php

namespace App\Http;

use Illuminate\Http\Response;

/**
 * A portal page served at an address deeper than the site root: the article
 * page under /berita/ and the 404 page at any missing address. Portal pages
 * are written with addresses relative to the site root (assets/…,
 * profil.html), which would resolve under the requested path, so they are
 * made root-relative. A <base> element is not used because it would break
 * the icons' <use href="#…"> references.
 */
class PortalPage
{
    /**
     * Render the view with root-relative addresses.
     */
    public static function render(string $view, int $status = 200): Response
    {
        return response(self::fromSiteRoot(view($view)->render()), $status);
    }

    /**
     * Make relative href and src addresses root-relative. Fragments (#…),
     * root-relative and full addresses stay as they are.
     */
    public static function fromSiteRoot(string $html): string
    {
        return preg_replace('/(?<=\s)(href|src)="(?![a-z][a-z0-9+.-]*:|\/|#)/i', '$1="/', $html);
    }
}
