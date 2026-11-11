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
     * Render the view with root-relative addresses. The global
     * AddPortalAssetVersions middleware versions its assets.
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

    /**
     * Give each portal stylesheet and script (assets/css, assets/js) a version
     * of its content (D-093). The domain's edge serves public/assets with a
     * one-year immutable cache, so a file whose address never changes would
     * keep returning visitors on the copy from before a deploy. Addresses that
     * already carry a query, and other assets (images, the versioned Lenis
     * folder), stay as they are.
     */
    public static function withAssetVersions(string $html): string
    {
        return preg_replace_callback(
            '/(?<=\s)(href|src)="(\/?)(assets\/(?:css|js)\/[\w.-]+\.(?:css|js))"/',
            function (array $match): string {
                $version = self::assetVersion($match[3]);

                return $version === null ? $match[0] : "{$match[1]}=\"{$match[2]}{$match[3]}?v={$version}\"";
            },
            $html,
        );
    }

    /**
     * The first 12 hex digits of the file's content hash, or null when the
     * file does not exist. Hashing the few small files on each page is
     * cheaper than keeping the versions in step with deploys.
     */
    public static function assetVersion(string $path): ?string
    {
        $file = public_path($path);

        return is_file($file) ? substr((string) hash_file('xxh128', $file), 0, 12) : null;
    }
}
