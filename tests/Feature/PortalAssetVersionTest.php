<?php

namespace Tests\Feature;

use App\Http\PortalPage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Tests\TestCase;

/**
 * The domain's edge caches public/assets for a year as immutable, so the
 * server versions the portal's stylesheets and scripts by their content
 * (D-093): a deploy reaches visitors whose browser kept the old files.
 */
class PortalAssetVersionTest extends TestCase
{
    private const string VERSIONED = '#(?<=\s)(?:href|src)="/?(assets/(?:css|js)/[\w.-]+\.(?:css|js))\?v=([0-9a-f]{12})"#';

    #[DataProviderExternal(PortalPagesTest::class, 'pagesAsWritten')]
    public function test_versions_every_stylesheet_and_script_of_each_portal_page_by_its_content(string $uri, string $view): void
    {
        $html = $this->get($uri)->assertOk()->getContent();
        $file = (string) file_get_contents(resource_path("views/portal/{$view}.blade.php"));

        preg_match_all(self::VERSIONED, $html, $versioned, PREG_SET_ORDER);
        $this->assertSame(preg_match_all('#(?<=\s)(?:href|src)="assets/(?:css|js)/#', $file), count($versioned));
        foreach ($versioned as [, $path, $version]) {
            $this->assertSame(substr((string) hash_file('xxh128', public_path($path)), 0, 12), $version, $path);
        }
        // Apart from the versions the page is served exactly as written
        $this->assertSame($file, preg_replace('#(assets/(?:css|js)/[\w.-]+\.(?:css|js))\?v=[0-9a-f]{12}#', '$1', $html));
    }

    public function test_versions_the_assets_of_pages_at_deeper_addresses_and_of_the_404_page(): void
    {
        $article = $this->get('/berita/pintu-resmi-diluncurkan')->assertOk()->getContent();
        $missing = $this->get('/tidak/ada/halaman')->assertNotFound()->getContent();

        foreach ([$article, $missing] as $html) {
            $this->assertMatchesRegularExpression('#<link rel="stylesheet" href="/assets/css/tokens\.css\?v=[0-9a-f]{12}">#', $html);
            $this->assertMatchesRegularExpression('#<script src="/assets/js/main\.js\?v=[0-9a-f]{12}" defer></script>#', $html);
            $this->assertSame(0, preg_match('#(?<=\s)(?:href|src)="/?assets/(?:css|js)/[^"?]+"#', $html));
        }
    }

    public function test_changes_the_version_with_the_content_and_leaves_other_addresses_alone(): void
    {
        $public = storage_path('framework/testing/aset-portal-'.bin2hex(random_bytes(4)));
        File::ensureDirectoryExists("{$public}/assets/css");
        File::ensureDirectoryExists("{$public}/assets/js");
        File::put("{$public}/assets/css/a.css", 'body { color: red; }');
        File::put("{$public}/assets/js/b.js", 'console.log(1);');
        $this->app->usePublicPath($public);

        try {
            $html = '<link rel="stylesheet" href="assets/css/a.css"><script src="/assets/js/b.js" defer></script>'
                .' <link href="assets/css/hilang.css"> <img src="assets/img/c.png"> <link href="assets/vendor/lenis-1.3.27/lenis.css">'
                .' <link href="assets/css/a.css?v=lama"> <a href="https://contoh.go.id/assets/css/a.css"> <img data-src="assets/js/b.js">';
            $a = substr((string) hash('xxh128', 'body { color: red; }'), 0, 12);
            $b = substr((string) hash('xxh128', 'console.log(1);'), 0, 12);

            $versioned = PortalPage::withAssetVersions($html);

            $this->assertSame(
                "<link rel=\"stylesheet\" href=\"assets/css/a.css?v={$a}\"><script src=\"/assets/js/b.js?v={$b}\" defer></script>"
                .' <link href="assets/css/hilang.css"> <img src="assets/img/c.png"> <link href="assets/vendor/lenis-1.3.27/lenis.css">'
                .' <link href="assets/css/a.css?v=lama"> <a href="https://contoh.go.id/assets/css/a.css"> <img data-src="assets/js/b.js">',
                $versioned,
            );
            $this->assertSame($versioned, PortalPage::withAssetVersions($versioned));

            File::put("{$public}/assets/css/a.css", 'body { color: blue; }');
            $this->assertNotSame($versioned, PortalPage::withAssetVersions($html));
            $this->assertSame(substr((string) hash('xxh128', 'body { color: blue; }'), 0, 12), PortalPage::assetVersion('assets/css/a.css'));
            $this->assertNull(PortalPage::assetVersion('assets/css/hilang.css'));
        } finally {
            File::deleteDirectory($public);
        }
    }

    public function test_leaves_responses_other_than_html_pages_alone(): void
    {
        Route::get('/uji-teks-aset', fn () => response(' href="assets/css/base.css"', 200, ['Content-Type' => 'text/plain']));

        $this->get('/uji-teks-aset')->assertOk()->assertContent(' href="assets/css/base.css"');
    }
}
