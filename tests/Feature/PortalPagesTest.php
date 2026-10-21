<?php

namespace Tests\Feature;

use App\Http\PortalPage;
use App\Models\Message;
use App\Models\Setting;
use Illuminate\Support\Facades\Vite;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PortalPagesTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function pages(): array
    {
        return [
            'beranda' => ['/', 'index'],
            'profil' => ['/profil', 'profil'],
            'visi misi' => ['/visi-misi', 'visi-misi'],
            'struktur organisasi' => ['/struktur-organisasi', 'struktur-organisasi'],
            'layanan' => ['/layanan', 'layanan'],
            'aplikasi' => ['/aplikasi', 'aplikasi'],
            'jadwal' => ['/jadwal', 'jadwal'],
            'informasi' => ['/informasi', 'informasi'],
            'berita' => ['/berita/pintu-resmi-diluncurkan', 'berita-detail'],
            'dokumentasi' => ['/dokumentasi', 'dokumentasi'],
            'unduhan' => ['/unduhan', 'unduhan'],
            'penerimaan' => ['/penerimaan', 'penerimaan'],
            'pemetaan' => ['/pemetaan', 'pemetaan'],
            'kontak' => ['/kontak', 'kontak'],
            'login' => ['/login', 'login'],
            'dashboard pegawai' => ['/dashboard-pegawai', 'dashboard-pegawai'],
            'design system' => ['/design-system', 'design-system'],
            'kebijakan privasi' => ['/kebijakan-privasi', 'kebijakan-privasi'],
            'syarat ketentuan' => ['/syarat-ketentuan', 'syarat-ketentuan'],
            'lacak antrean' => ['/lacak/AbCdEfGhIjKlMnOpQrStUv', 'lacak'],
            'lacak pesan' => ['/pesan/AbCdEfGhIjKlMnOpQrStUv', 'lacak-pesan'],
        ];
    }

    /**
     * The portal pages served exactly as their files are written: all but
     * the article and the two tracking pages, whose addresses are made
     * root-relative.
     *
     * @return array<string, array{string, string}>
     */
    public static function pagesAsWritten(): array
    {
        return array_filter(self::pages(), fn (array $page): bool => ! in_array($page[1], ['berita-detail', 'lacak', 'lacak-pesan'], true));
    }

    /**
     * The portal pages served as written that carry the shared header (all
     * but the sign-in and the former dashboard pages).
     *
     * @return array<string, array{string, string}>
     */
    public static function pagesWithHeader(): array
    {
        return array_filter(
            self::pagesAsWritten(),
            fn (array $page): bool => str_contains((string) file_get_contents(dirname(__DIR__, 2)."/resources/views/portal/{$page[1]}.blade.php"), '<!-- @header -->'),
        );
    }

    #[DataProvider('pagesAsWritten')]
    public function test_serves_each_portal_page_byte_for_byte_as_written(string $uri, string $view): void
    {
        $response = $this->get($uri);

        $response->assertOk()->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        $this->assertSame(file_get_contents(resource_path("views/portal/{$view}.blade.php")), $response->getContent());
    }

    #[DataProvider('pages')]
    public function test_loads_the_portal_theme_after_the_portal_stylesheets(string $uri, string $view): void
    {
        $html = $this->get($uri)->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#<link rel="stylesheet" href="tema-portal\.css"( /)?>#', (string) file_get_contents(resource_path("views/portal/{$view}.blade.php")));

        preg_match_all('/<link rel="stylesheet" href="([^"]+)"/', $html, $links);
        $this->assertSame('/tema-portal.css', '/'.ltrim(end($links[1]), '/'));
        $this->assertSame(1, substr_count($html, 'tema-portal.css'));
    }

    public function test_leaves_the_home_page_faq_entries_to_the_public_api(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(1, preg_match('/<section[^>]*\bid="faq"[^>]*>.*?<\/section>/s', $html, $faq));
        $outsideTemplate = preg_replace('/<template data-faq-item>.*?<\/template>/s', '', $faq[0]);
        $this->assertStringContainsString('data-faq-list', $outsideTemplate);
        $this->assertStringNotContainsString('<details', $outsideTemplate);
    }

    public function test_leaves_the_schedule_of_service_outside_the_office_to_the_public_api(): void
    {
        $home = $this->get('/')->assertOk()->getContent();
        $schedule = $this->get('/jadwal')->assertOk()->getContent();

        $this->assertSame(1, preg_match('/<ul[^>]*data-keliling-today>(.*?)<\/ul>/s', $home, $today));
        $this->assertSame(1, preg_match('/<div[^>]*data-keliling-week[^>]*>(.*?)<\/div>/s', $schedule, $week));
        $this->assertSame(1, preg_match('/<div[^>]*data-keliling-gerai>(.*?)<\/div>/s', $schedule, $outlets));
        $this->assertSame(1, preg_match('/<div[^>]*data-keliling-sementara>(.*?)<\/div>/s', $schedule, $counters));
        $this->assertStringNotContainsString('Kantor Kelurahan', $today[1]);
        $this->assertStringContainsString('Memuat jadwal', $today[1]);
        $this->assertSame('<p class="muted" role="status">Memuat jadwal minggu ini…</p>', trim($week[1]));
        $this->assertSame('<p class="muted">Memuat gerai tetap…</p>', trim($outlets[1]));
        $this->assertSame('<p class="muted">Memuat loket sementara…</p>', trim($counters[1]));
    }

    #[TestWith(['/'])]
    #[TestWith(['/jadwal'])]
    #[TestWith(['/kontak'])]
    #[TestWith(['/layanan'])]
    public function test_leaves_the_opening_hours_to_the_public_api(string $uri): void
    {
        $html = $this->get($uri)->assertOk()->getContent();

        $this->assertStringNotContainsString('Senin–Kamis', $html);
        $this->assertStringNotContainsString('tutup 15.00', $html);
        $this->assertStringContainsString('<ul role="list" data-jam-ringkas>', $html);
    }

    public function test_leaves_the_service_board_to_the_public_api(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('A-042', $html);
        $this->assertStringNotContainsString('data contoh', $html);
        $this->assertStringContainsString('<ul class="board__counters" role="list" data-papan-loket></ul>', $html);
        $this->assertStringContainsString('<p class="tile__value board__big" data-papan-nomor>—</p>', $html);
    }

    #[TestWith(['/'])]
    #[TestWith(['/layanan'])]
    public function test_offers_the_tax_check_by_plate_number_and_chassis_digits(string $uri): void
    {
        $html = $this->get($uri)->assertOk()->getContent();

        $this->assertSame(1, preg_match('/<section[^>]*\bid="simulasi"[^>]*>.*?<\/section>/s', $html, $check));
        $this->assertStringContainsString('data-cek-pajak', $check[0]);
        $this->assertMatchesRegularExpression('/<input[^>]*\bname="nopol"/', $check[0]);
        $this->assertMatchesRegularExpression('/<input[^>]*\bname="rangka"[^>]*\bmaxlength="5"/', $check[0]);
        $this->assertStringContainsString('<div class="bento cek__hasil" data-cek-hasil hidden>', $check[0]);
        $this->assertStringContainsString('<script type="module" src="skrip/cek-pajak"></script>', $html);
        $this->assertStringNotContainsString('data-sim', $html);
        $this->assertStringNotContainsString('data-tarif', $html);
    }

    #[TestWith(['/skrip/cek-pajak', 'resources/js/portal/cek-pajak.ts', 'http://localhost/build/assets/cek-pajak-a1b2c3.js'])]
    #[TestWith(['/skrip/portal', 'resources/js/portal/main.tsx', 'http://localhost/build/assets/main-d4e5f6.js'])]
    public function test_sends_the_portal_to_its_built_scripts_without_letting_the_address_be_cached(string $uri, string $entry, string $built): void
    {
        Vite::shouldReceive('asset')->once()->with($entry)->andReturn($built);

        $response = $this->get($uri);

        $response->assertFound()
            ->assertRedirect($built)
            ->assertHeader('Cache-Control', 'no-cache, private');
        $this->assertSame([], $response->headers->getCookies());
    }

    public function test_leaves_the_transparency_dashboard_to_the_public_api(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(1, preg_match('/<section[^>]*\bid="transparansi"[^>]*>.*?<\/section>/s', $html, $dashboard));
        foreach (['12.480', '9.316', 'Data ilustrasi', 'data-dash-range', 'Hari ini'] as $example) {
            $this->assertStringNotContainsString($example, $dashboard[0]);
        }
        $this->assertStringContainsString('<p class="dash__status" role="status">Memuat statistik layanan…</p>', $dashboard[0]);
        $this->assertStringContainsString('data-dash-chart hidden', $dashboard[0]);
    }

    #[DataProvider('pages')]
    public function test_leaves_the_footer_contact_details_to_the_public_api(string $uri, string $view): void
    {
        $html = $this->get($uri)->assertOk()->getContent();

        foreach (['alamat kantor', '(0380)', 'example.go.id', '8xx', '@samsatkotakupang'] as $placeholder) {
            $this->assertStringNotContainsString($placeholder, $html, "{$view} still shows {$placeholder}");
        }
        if (str_contains($html, '<footer class="footer">')) {
            $this->assertStringContainsString('<p data-kontak-alamat>Memuat alamat…</p>', $html, $view);
            $this->assertMatchesRegularExpression('/<ul role="list" data-kontak-hubungi>\s*<li><a href="\/?kontak.html#pengaduan">Kanal pengaduan<\/a><\/li>\s*<\/ul>/', $html, $view);
        }
    }

    #[DataProvider('pages')]
    public function test_keeps_the_initial_footer_texts_in_the_markup_for_the_public_api_to_replace(string $uri, string $view): void
    {
        $html = $this->get($uri)->assertOk()->getContent();
        $text = fn (string $html): string => trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        if (! str_contains($html, '<footer class="footer">')) {
            $this->assertStringNotContainsString('data-situs-', $html, $view);

            return;
        }

        $this->assertSame(1, preg_match('/<p [^>]*data-situs-deskripsi>(.*?)<\/p>/s', $html, $description), $view);
        $this->assertSame(Setting::INITIAL_SITE['deskripsi'], $text($description[1]), $view);
        $this->assertSame(1, preg_match('/<p data-situs-hak-cipta>(.*?)<\/p>/s', $html, $copyright), $view);
        $this->assertSame(Setting::INITIAL_SITE['hakCipta'], $text($copyright[1]), $view);
    }

    public function test_keeps_the_initial_announcement_in_the_markup_for_the_public_api_to_replace(): void
    {
        $html = $this->get('/informasi')->assertOk()->getContent();
        $announcement = Setting::INITIAL_SITE['bilahPengumuman'];

        $this->assertSame(1, preg_match('/<a class="card card--link" href="([^"]*)"[^>]*data-situs-pengumuman>(.*?)<\/a>/s', $html, $bar));
        $this->assertSame($announcement['tautanUrl'], $bar[1]);
        $this->assertStringContainsString("data-situs-pengumuman-teks>{$announcement['teks']}</span>", $bar[2]);
        $this->assertStringContainsString("data-situs-pengumuman-label>{$announcement['tautanLabel']}<svg", $bar[2]);
    }

    public function test_leaves_the_contact_page_details_and_complaint_channels_to_the_public_api(): void
    {
        $html = $this->get('/kontak')->assertOk()->getContent();

        $this->assertSame(1, preg_match('/<div class="channels"[^>]*>(.*?)<\/div>/s', $html, $channels));
        $this->assertSame('<p class="channels__status" role="status">Memuat kanal pengaduan…</p>', trim($channels[1]));
        foreach (['lapor.go.id', 'Parkir tersedia', 'tiga hari kerja', 'maps.google.com', 'diisi setelah verifikasi'] as $example) {
            $this->assertStringNotContainsString($example, $html);
        }
        foreach (['telepon', 'whatsapp', 'email', 'sosial'] as $item) {
            $this->assertStringContainsString("<li data-kontak-{$item} hidden>", $html);
        }
        $this->assertStringContainsString('<span data-kontak-alamat-baris>Memuat alamat…</span>', $html);
    }

    public function test_keeps_the_illustrated_map_until_the_script_places_the_google_map_at_the_office(): void
    {
        $html = $this->get('/kontak')->assertOk()->getContent();

        $this->assertStringContainsString('<div class="map" data-kontak-peta-wadah>', $html);
        $this->assertStringContainsString('<p data-kontak-peta-keterangan>Peta ilustrasi. Gunakan tautan untuk petunjuk arah.</p>', $html);
        $this->assertStringNotContainsString('<iframe', $html);
    }

    public function test_leaves_the_vision_missions_values_and_pledge_to_the_public_api(): void
    {
        $html = $this->get('/visi-misi')->assertOk()->getContent();

        foreach (['NTT Maju', 'Memastikan infrastruktur', 'Lima misi', 'Tata kelola informasi', 'Berorientasi pelayanan', 'Dengan ini, kami menyatakan'] as $text) {
            $this->assertStringNotContainsString($text, $html);
        }
        $this->assertStringContainsString('<p class="small muted" style="margin-top:16px" role="status" data-visi-status>Memuat visi dan misi…</p>', $html);
        $this->assertStringContainsString('<ol class="numlist" role="list" data-visi-misi data-reveal></ol>', $html);
        $this->assertSame(4, preg_match_all('/<section [^>]*data-visi-bagian="[a-z]+"[^>]* hidden>/', $html));
        // Tanpa berkas maklumat di server, tombol unduh tetap ke halaman Regulasi & Unduhan
        $this->assertStringContainsString('href="unduhan.html" data-visi-unduh><svg class="i" aria-hidden="true"><use href="#i-download"/></svg>Unduh maklumat (PDF)</a>', $html);
    }

    public function test_leaves_the_profile_text_to_the_public_api(): void
    {
        $html = $this->get('/profil')->assertOk()->getContent();

        foreach (['Melayani pajak daerah', '[Nama Kepala UPTD]', 'Bagian dari BPAD NTT', 'Pendataan objek', 'Kami ingin setiap warga'] as $text) {
            $this->assertStringNotContainsString($text, $html);
        }
        $this->assertStringContainsString('<p class="small muted" style="margin-top:16px" role="status" data-profil-status>Memuat profil…</p>', $html);
        $this->assertSame(3, preg_match_all('/<section [^>]*data-profil-bagian="[a-z]+" hidden>/', $html));
        $this->assertStringContainsString('<div data-profil-tetap><dt>Dasar hukum</dt>', $html);
        // Foto gedung bawaan tampil sampai foto kantor diunggah; foto Kepala UPTD menggantikan ilustrasinya
        $this->assertSame(1, preg_match('#<img src="(assets/img/kantor-samsat-kota-kupang-1600\.webp)" srcset="(assets/img/kantor-samsat-kota-kupang-800\.webp) 800w, [^"]+"[^>]* data-profil-foto>#', $html, $photo));
        $this->assertFileExists(public_path($photo[1]));
        $this->assertFileExists(public_path($photo[2]));
        $this->assertStringNotContainsString('ganti dengan foto resmi</span>', substr($html, 0, (int) strpos($html, 'data-profil-sambutan-foto')));
        $this->assertStringContainsString('data-reveal data-profil-sambutan-foto>', $html);
    }

    public function test_leaves_the_service_requirements_to_the_public_api(): void
    {
        $services = $this->get('/layanan')->assertOk()->getContent();
        $home = $this->get('/')->assertOk()->getContent();

        foreach ([$services, $home] as $html) {
            $this->assertStringNotContainsString('Fotokopi BPKB', $html);
            $this->assertStringNotContainsString('Fotokopi KTP', $html);
        }
        $this->assertStringContainsString('<nav class="catlist" data-spy data-layanan-nav></nav>', $services);
        $this->assertStringContainsString('<p class="muted" role="status" data-layanan-status>Memuat persyaratan layanan…</p>', $services);
        $this->assertStringNotContainsString('diverifikasi petugas, Oktober 2026', $services);
        $this->assertSame(1, preg_match('/<section[^>]*\bid="layanan-utama"[^>]*>.*?<\/section>/s', $home, $section));
        $this->assertStringNotContainsString('role="tab"', $section[0]);
        $this->assertStringContainsString('<p class="muted" role="status">Memuat persyaratan layanan…</p>', $section[0]);
    }

    #[DataProvider('pagesWithHeader')]
    public function test_offers_the_search_in_the_navbar_of_every_page(string $uri, string $view): void
    {
        $html = $this->get($uri)->assertOk()->getContent();

        $this->assertStringContainsString('<input class="nav__cari-input" type="search" placeholder="Cari…" aria-label="Cari di PINTU"', $html);
        $this->assertStringContainsString('aria-controls="cari-panel" data-cari-buka>', $html);
        $this->assertStringContainsString('<div class="cari-panel" id="cari-panel" hidden data-cari-panel>', $html);
        $this->assertStringContainsString('<div class="cari-hasil" id="cari-hasil" role="listbox" aria-label="Hasil pencarian" data-cari-hasil></div>', $html);
        $this->assertSame(2, substr_count($html, 'data-cari-input'));
        $this->assertStringContainsString('<script src="assets/js/main.js" defer></script>', $html);
    }

    /**
     * Every portal page with the shared header, including those served with
     * addresses from the site root and the 404 page, with its status.
     *
     * @return array<string, array{string, int}>
     */
    public static function pagesWithLenis(): array
    {
        $pages = array_filter(
            self::pages(),
            fn (array $page): bool => str_contains((string) file_get_contents(dirname(__DIR__, 2)."/resources/views/portal/{$page[1]}.blade.php"), '<!-- @header -->'),
        );

        return [...array_map(fn (array $page): array => [$page[0], 200], $pages), '404' => ['/halaman-tidak-ada', 404]];
    }

    #[DataProvider('pagesWithLenis')]
    public function test_loads_lenis_before_the_theme_and_the_portal_script_on_every_page_with_the_header(string $uri, int $status): void
    {
        $html = $this->get($uri)->assertStatus($status)->getContent();

        $this->assertMatchesRegularExpression('#<link rel="stylesheet" href="/?assets/vendor/lenis-1\.3\.27/lenis\.css"( /)?>\s*<link rel="stylesheet" href="/?tema-portal\.css"( /)?>#', $html);
        $this->assertMatchesRegularExpression('#<script src="/?assets/vendor/lenis-1\.3\.27/lenis\.min\.js" defer></script>\s*<script src="/?assets/js/main\.js" defer></script>#', $html);
        $this->assertSame(2, substr_count($html, 'assets/vendor/lenis-'));
    }

    #[TestWith(['/login'])]
    #[TestWith(['/dashboard-pegawai'])]
    public function test_leaves_the_pages_without_the_header_without_lenis(string $uri): void
    {
        $this->assertStringNotContainsString('lenis', $this->get($uri)->assertOk()->getContent());
    }

    public function test_keeps_the_lenis_build_of_the_version_in_its_folder_name_with_its_licence(): void
    {
        $folder = public_path('assets/vendor/lenis-1.3.27');

        $this->assertStringStartsWith('(function(){var e=`1.3.27`;', (string) file_get_contents("{$folder}/lenis.min.js"));
        $this->assertStringContainsString('.lenis:not(.lenis-autoToggle).lenis-stopped', (string) file_get_contents("{$folder}/lenis.css"));
        $this->assertStringContainsString('Copyright (c) 2024 darkroom.engineering', (string) file_get_contents("{$folder}/LICENSE"));
    }

    public function test_leaves_the_service_guide_to_its_component_above_the_services(): void
    {
        $html = $this->get('/layanan')->assertOk()->getContent();

        $this->assertSame(1, preg_match('/<section[^>]*\bid="panduan"[^>]*>.*?<\/section>/s', $html, $guide));
        $this->assertStringContainsString('Layanan apa yang saya perlukan?', $guide[0]);
        $this->assertStringContainsString("<div data-react=\"panduan-layanan\">\n        <p class=\"muted\" role=\"status\">Memuat panduan…</p>", str_replace("\r\n", "\n", $guide[0]));
        $this->assertLessThan(strpos($html, 'data-layanan-daftar'), strpos($html, 'id="panduan"'));
        $this->assertStringContainsString('<script type="module" src="skrip/portal"></script>', $html);
    }

    public function test_leaves_the_documents_to_the_public_api(): void
    {
        $html = $this->get('/unduhan')->assertOk()->getContent();

        $this->assertStringNotContainsString('class="doc"', $html);
        $this->assertStringNotContainsString('[ukuran]', $html);
        $this->assertStringNotContainsString('class="proto-note"', $html);
        $this->assertStringContainsString('<ul class="docs" id="docs" role="list" data-reveal data-dokumen-daftar></ul>', $html);
        $this->assertStringContainsString('<p class="center muted" role="status" data-dokumen-status style="padding:48px 0">Memuat dokumen…</p>', $html);
        $this->assertStringContainsString('<div class="toolbar" style="margin-bottom:28px" data-reveal data-dokumen-saringan hidden>', $html);
    }

    public function test_leaves_the_revenue_report_to_its_component_and_the_public_api(): void
    {
        $html = $this->get('/penerimaan')->assertOk()->getContent();

        $this->assertStringContainsString('<div class="penerimaan" data-react="laporan-penerimaan">', $html);
        $this->assertStringContainsString('<p class="center muted" role="status" style="padding:48px 0">Memuat laporan penerimaan…</p>', $html);
        $this->assertStringContainsString('<script type="module" src="skrip/portal"></script>', $html);
        // The masking rule is stated on the page, whatever the component shows (D-048, D-049)
        $this->assertStringContainsString('Setiap kelompok hanya menampilkan jumlah transaksi dan rupiahnya. Kelompok dengan kurang dari 3 transaksi digabung menjadi “Lainnya (disamarkan)”', $html);
    }

    #[DataProvider('pagesWithHeader')]
    public function test_links_the_revenue_report_from_the_information_menu(string $uri, string $view): void
    {
        $html = $this->get($uri)->assertOk()->getContent();

        $current = $view === 'penerimaan' ? ' aria-current="page"' : '';
        $this->assertStringContainsString("<a class=\"nav__menu-link\" data-nav=\"penerimaan\"{$current} href=\"penerimaan.html\">", $html);
        $this->assertStringContainsString("<a data-nav=\"penerimaan\"{$current} href=\"penerimaan.html\">Laporan Penerimaan</a>", $html);
    }

    #[DataProvider('pagesWithHeader')]
    public function test_links_the_apps_page_from_the_services_menu(string $uri, string $view): void
    {
        $html = $this->get($uri)->assertOk()->getContent();

        $current = $view === 'aplikasi' ? ' aria-current="page"' : '';
        $this->assertStringContainsString("<a class=\"nav__menu-link\" data-nav=\"aplikasi\"{$current} href=\"aplikasi.html\">", $html);
        $this->assertStringContainsString("<a data-nav=\"aplikasi\"{$current} href=\"aplikasi.html\">Aplikasi &amp; Situs</a>", $html);
    }

    public function test_leaves_the_apps_to_the_public_api(): void
    {
        $html = $this->get('/aplikasi')->assertOk()->getContent();

        $this->assertStringNotContainsString('class="card card--pad app"', $html);
        $this->assertMatchesRegularExpression('#<div class="apps" data-aplikasi-daftar>\s*<p class="muted center" role="status">Memuat daftar aplikasi…</p>\s*</div>#', $html);
        $this->assertStringContainsString('<body id="top" data-page="aplikasi" data-group="layanan">', $html);
        $this->get('/aplikasi.html')->assertMovedPermanently()->assertRedirect('/aplikasi');
    }

    public function test_summarises_the_revenue_report_and_the_tax_map_in_the_homepage_dashboard(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(1, preg_match('/<section[^>]*\bid="transparansi"[^>]*>.*?<\/section>/s', $html, $section));
        // Both are tabs of the dashboard (D-055), with a link to the whole page inside the panel
        $this->assertSame(5, preg_match_all('#data-set="(transaksi|penerimaan|pemetaan|keliling|kepuasan)"#', $section[0], $tabs));
        $this->assertSame(['transaksi', 'penerimaan', 'pemetaan', 'keliling', 'kepuasan'], $tabs[1]);
        $this->assertMatchesRegularExpression('#<a class="dash__more" href="penerimaan\.html" data-dash-tautan hidden\s*><span data-dash-tautan-teks>Lihat laporan penerimaan</span#', $section[0]);
        $this->assertStringNotContainsString('class="link-arrow" href="penerimaan.html"', $section[0]);
        $this->assertStringNotContainsString('href="pemetaan.html"', $section[0]);
    }

    public function test_leaves_the_tax_potential_map_to_its_component_and_the_public_api(): void
    {
        $html = $this->get('/pemetaan')->assertOk()->getContent();

        $this->assertStringContainsString('<div class="pemetaan" data-react="pemetaan-pkb">', $html);
        $this->assertStringContainsString('<p class="center muted" role="status" style="padding:48px 0">Memuat pemetaan PKB…</p>', $html);
        $this->assertStringContainsString('<script type="module" src="skrip/portal"></script>', $html);
        // The masking rule is stated on the page, whatever the component shows (D-052)
        $this->assertStringContainsString('Angka yang menggambarkan kurang dari 3 kendaraan disamarkan, bersama angka terkecil lain bila angkanya masih dapat dihitung dari total.', $html);
        // The map's boundaries are credited to their source
        $this->assertStringContainsString('Batas kecamatan dan kelurahan pada peta dari Batas Desa/Kelurahan skala 1:10.000 edisi 2022, Badan Informasi Geospasial', $html);
    }

    #[DataProvider('pagesWithHeader')]
    public function test_links_the_tax_potential_map_from_the_information_menu(string $uri, string $view): void
    {
        $html = $this->get($uri)->assertOk()->getContent();

        $current = $view === 'pemetaan' ? ' aria-current="page"' : '';
        $this->assertStringContainsString("<a class=\"nav__menu-link\" data-nav=\"pemetaan\"{$current} href=\"pemetaan.html\">", $html);
        $this->assertStringContainsString("<a data-nav=\"pemetaan\"{$current} href=\"pemetaan.html\">Pemetaan PKB</a>", $html);
    }

    public function test_leaves_the_news_to_the_public_api(): void
    {
        $news = $this->get('/informasi')->assertOk()->getContent();
        $article = $this->get('/berita/pintu-resmi-diluncurkan')->assertOk()->getContent();
        $home = $this->get('/')->assertOk()->getContent();

        foreach (['class="post"', 'class="pager"', 'Pembayaran QRIS', 'contoh tata letak', '<li><a href="berita-detail.html">'] as $example) {
            $this->assertStringNotContainsString($example, $news);
        }
        $this->assertStringContainsString('<section class="section section--tight" data-berita-utama-bagian hidden>', $news);
        $this->assertStringContainsString('<div class="posts" id="posts" data-berita-daftar></div>', $news);
        $this->assertStringContainsString('<p class="center muted" role="status" data-berita-status style="padding:48px 0">Memuat berita…</p>', $news);
        $this->assertStringContainsString('<ul class="notice-list" role="list" data-berita-pengumuman></ul>', $news);

        foreach (['Selama ini, informasi persyaratan', '27 Oktober 2026', 'class="post"', 'Kepala Subbagian Tata Usaha'] as $example) {
            $this->assertStringNotContainsString($example, $article);
        }
        $this->assertStringContainsString('<h1 class="h-display" style="margin-top:22px" data-berita-judul>Memuat tulisan…</h1>', $article);
        $this->assertStringContainsString('<div class="prose" data-berita-isi></div>', $article);

        $this->assertStringNotContainsString('Berlaku untuk keterlambatan pajak tahunan', $home);
        $this->assertStringContainsString('data-papan-pengumuman-judul>', $home);
    }

    public function test_leaves_the_activity_photos_to_the_public_api(): void
    {
        $html = $this->get('/dokumentasi')->assertOk()->getContent();

        foreach (['class="gallery__item', 'Placeholder ilustrasi', 'Oktober 2026 · 10 foto', 'data-filter-group="gallery"', '<a class="chip'] as $example) {
            $this->assertStringNotContainsString($example, $html);
        }
        $this->assertStringContainsString('<div class="gallery" id="gallery" data-reveal data-dok-galeri></div>', $html);
        $this->assertStringContainsString('<p class="center muted" role="status" data-dok-status style="padding:48px 0">Memuat dokumentasi…</p>', $html);
        $this->assertStringContainsString('<div class="toolbar" style="margin-bottom:28px" data-reveal data-dok-saringan hidden>', $html);
        $this->assertStringContainsString('<section class="section" data-dok-arsip hidden>', $html);
        $this->assertStringContainsString('<dialog class="dialog lightbox" id="lightbox" aria-label="Pratinjau foto">', $html);
    }

    public function test_leaves_the_organisation_chart_and_duties_to_the_public_api(): void
    {
        $html = $this->get('/struktur-organisasi')->assertOk()->getContent();

        foreach (['class="org__node', 'class="org__staff"', '<details class="acc"', '[Nama]', 'class="proto-note"'] as $static) {
            $this->assertStringNotContainsString($static, $html);
        }
        $this->assertMatchesRegularExpression('#<div class="org" data-struktur-bagan>\s*<p class="muted center" role="status">Memuat struktur organisasi…</p>\s*</div>#', $html);
        $this->assertStringContainsString('<div class="accordion" data-reveal data-struktur-tugas></div>', $html);
        $this->assertStringContainsString('Susunan unit mengikuti Renstra BPAD 2025–2029 (Pergub NTT Nomor 96 Tahun 2023).', $html);
    }

    public function test_serves_the_article_page_with_addresses_from_the_site_root(): void
    {
        $response = $this->get('/berita/pintu-resmi-diluncurkan');
        $html = $response->assertOk()->assertHeader('Content-Type', 'text/html; charset=UTF-8')->getContent();

        $this->assertSame(0, preg_match('/\s(?:href|src)="(?![a-z][a-z0-9+.-]*:|\/|#)/i', $html));
        $this->assertStringContainsString('<link rel="stylesheet" href="/assets/css/tokens.css">', $html);
        $this->assertStringContainsString('<script src="/assets/js/main.js" defer></script>', $html);
        $this->assertStringContainsString('<a class="link-arrow small" href="/informasi.html">', $html);
        $this->assertStringContainsString('<a class="skip-link" href="#main">', $html);
        $this->assertStringContainsString('<use href="#i-link"/>', $html);
        $this->assertStringContainsString('href="https://wa.me/?text=PINTU%20Samsat%20Kota%20Kupang"', $html);
        $this->assertSame(
            file_get_contents(resource_path('views/portal/berita-detail.blade.php')),
            preg_replace('/(\s(?:href|src)=")\/(?!\/)/', '$1', $html),
        );
        $this->assertSame([], $response->headers->getCookies());
    }

    public function test_serves_the_queue_tracking_page_with_addresses_from_the_site_root_and_kept_out_of_search(): void
    {
        $response = $this->get('/lacak/AbCdEfGhIjKlMnOpQrStUv');
        $html = $response->assertOk()->assertHeader('Content-Type', 'text/html; charset=UTF-8')->getContent();

        $this->assertSame(0, preg_match('/\s(?:href|src)="(?![a-z][a-z0-9+.-]*:|\/|#)/i', $html));
        $this->assertStringContainsString('<meta name="robots" content="noindex, nofollow">', $html);
        $this->assertStringContainsString('<div class="lacak" data-react="lacak-antrean">', $html);
        $this->assertStringContainsString('<script type="module" src="/skrip/portal"></script>', $html);
        $this->assertSame(
            file_get_contents(resource_path('views/portal/lacak.blade.php')),
            preg_replace('/(\s(?:href|src)=")\/(?!\/)/', '$1', $html),
        );
        $this->assertSame([], $response->headers->getCookies());
    }

    public function test_serves_the_message_tracking_page_with_addresses_from_the_site_root_and_kept_out_of_search(): void
    {
        $response = $this->get('/pesan/AbCdEfGhIjKlMnOpQrStUv');
        $html = $response->assertOk()->assertHeader('Content-Type', 'text/html; charset=UTF-8')->getContent();

        $this->assertSame(0, preg_match('/\s(?:href|src)="(?![a-z][a-z0-9+.-]*:|\/|#)/i', $html));
        $this->assertStringContainsString('<meta name="robots" content="noindex, nofollow">', $html);
        $this->assertStringContainsString('<div class="lacak-pesan" data-react="lacak-pesan">', $html);
        $this->assertStringContainsString('<script type="module" src="/skrip/portal"></script>', $html);
        $this->assertSame(
            file_get_contents(resource_path('views/portal/lacak-pesan.blade.php')),
            preg_replace('/(\s(?:href|src)=")\/(?!\/)/', '$1', $html),
        );
        $this->assertSame([], $response->headers->getCookies());
    }

    public function test_offers_the_message_lookup_by_ticket_and_contact_on_the_contact_page(): void
    {
        $html = $this->get('/kontak')->assertOk()->getContent();

        $this->assertSame(1, preg_match('/<section[^>]*\bid="lacak-pesan"[^>]*>.*?<\/section>/s', $html, $section));
        $this->assertStringContainsString('<form class="card card--xl form-card" data-lacak-pesan-form novalidate data-reveal>', $section[0]);
        $this->assertStringContainsString('name="tiket" required', $section[0]);
        $this->assertStringContainsString('name="kontak" required', $section[0]);
    }

    #[TestWith(['/pesan/AbCdEfGhIjKlMnOpQrStU'])]
    #[TestWith(['/pesan/AbCdEfGhIjKlMnOpQrStUvW'])]
    #[TestWith(['/pesan/PINTU-7KQ2MX'])]
    #[TestWith(['/pesan'])]
    #[TestWith(['/lacak/AbCdEfGhIjKlMnOpQrStU'])]
    #[TestWith(['/lacak/AbCdEfGhIjKlMnOpQrStUvW'])]
    #[TestWith(['/lacak/AbCdEfGhIjKlMnOpQrSt-v'])]
    #[TestWith(['/lacak'])]
    public function test_answers_404_for_a_tracking_address_without_a_code(string $uri): void
    {
        $this->get($uri)->assertNotFound();
    }

    public function test_makes_only_relative_addresses_root_relative(): void
    {
        $html = '<link href="assets/a.css"><script src="assets/a.js"></script><a href="profil.html#visi"><a href="/kontak"><a href="#main"><use href="#i-x"/><a href="https://wa.me/"><a href="mailto:a@b.go.id">';

        $this->assertSame(
            '<link href="/assets/a.css"><script src="/assets/a.js"></script><a href="/profil.html#visi"><a href="/kontak"><a href="#main"><use href="#i-x"/><a href="https://wa.me/"><a href="mailto:a@b.go.id">',
            PortalPage::fromSiteRoot($html),
        );
    }

    #[TestWith(['/berita/Judul'])]
    #[TestWith(['/berita/judul--ganda'])]
    #[TestWith(['/berita/judul/lain'])]
    public function test_answers_404_for_an_article_address_without_a_slug(string $uri): void
    {
        $this->get($uri)->assertNotFound();
    }

    #[TestWith(['/berita-detail'])]
    #[TestWith(['/berita-detail.html'])]
    public function test_sends_the_former_example_article_to_the_news_list(string $uri): void
    {
        $this->get($uri)->assertMovedPermanently()->assertRedirect('/informasi');
    }

    public function test_keeps_the_initial_homepage_texts_in_the_markup_for_the_public_api_to_replace(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $text = preg_replace('/[ \t\r\n]+/', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        $initial = Setting::INITIAL_HOMEPAGE;
        // The rotating words only appear while the title's word rolls through them (D-045)
        unset($initial['hero']['kataBergilir']);
        array_walk_recursive($initial, function (mixed $value, string|int $key) use ($text): void {
            if (is_string($value) && $key !== 'id' && $key !== 'tombolUrl') {
                $this->assertStringContainsString($value, $text);
            }
        });
        $this->assertSame('SAMSAT', Setting::rotatingWordIn($initial['hero']['judul'], Setting::INITIAL_HOMEPAGE['hero']['kataBergilir']));
        $this->assertStringContainsString('<h1 class="h-hero hero__title" data-react="judul-pembuka" data-reveal>', $html);
        $this->assertStringContainsString('<script type="module" src="skrip/portal"></script>', $html);
        foreach (['data-beranda-tombol', 'data-beranda-catatan ', 'data-beranda-nilai', 'data-beranda-mitra', 'data-beranda-kanal', 'data-beranda-catatan-notis', 'data-beranda-wajib', 'data-beranda-jaminan'] as $hook) {
            $this->assertStringContainsString($hook, $html);
        }
        $this->assertSame(4, substr_count($html, 'data-beranda-judul="'));
        $this->assertSame(6, substr_count($html, 'data-beranda-teks="'));
        // The video button stays hidden until the public API gives a video (D-040)
        $this->assertMatchesRegularExpression('/<button\s+class="play-pill"[^>]*\sdata-beranda-video\s+hidden\s*>/', $html);
        foreach (['data-beranda-video-label', 'data-beranda-video-durasi', 'data-beranda-video-pemutar', 'data-beranda-video-judul'] as $hook) {
            $this->assertStringContainsString($hook, $html);
        }
        $this->assertStringNotContainsString('2:14', $html);
    }

    public function test_shows_only_the_province_emblem_until_the_hero_component_brings_out_the_partner_logos(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(1, preg_match('#<div class="hero__lambang" data-react="lambang-pembuka">(.*?)</div>#s', $html, $emblems));
        $this->assertMatchesRegularExpression('#<img\s+class="emblem hero__emblem"\s+src="assets/img/lambang-ntt-360\.webp"[^>]*\sdata-lambang="utama"\s*/>#', $emblems[1]);
        // Ditlantas on the left and Jasa Raharja on the right wait in a template, never shown without the component (D-046)
        $this->assertSame(1, preg_match('#<template data-lambang-mitra>(.*?)</template>#s', $emblems[1], $partners));
        foreach (['kiri' => 'logo-ditlantas-240.webp', 'kanan' => 'logo-jasa-raharja-240.webp'] as $position => $file) {
            $this->assertMatchesRegularExpression('#<img\s+src="assets/img/'.preg_quote($file, '#').'"[^>]*\sdata-lambang="'.$position.'"\s*/>#', $partners[1]);
            $this->assertFileExists(public_path("assets/img/{$file}"));
        }
    }

    public function test_sends_the_contact_form_to_the_message_api_with_the_services_it_accepts(): void
    {
        $html = $this->get('/kontak')->assertOk()->getContent();

        $this->assertSame(1, preg_match('/<form[^>]*data-pesan-form[^>]*>(.*?)<\/form>/s', $html, $form));
        $this->assertStringNotContainsString('data-demo-form', $html);
        preg_match_all('/<option value="([^"]*)">/', $form[1], $options);
        $this->assertSame(Message::SERVICES, array_map(fn (string $value): string => html_entity_decode($value), $options[1]));
        foreach (['name="isi"', 'name="kontak"', 'name="persetujuan"', 'name="kode_tambahan" tabindex="-1" autocomplete="off"'] as $field) {
            $this->assertStringContainsString($field, $form[1]);
        }
    }

    public function test_fills_the_opening_hours_table_only_from_the_public_api(): void
    {
        $html = $this->get('/jadwal')->assertOk()->getContent();

        $this->assertSame(1, preg_match('/<tbody data-jam-tabel>(.*?)<\/tbody>/s', $html, $rows));
        $this->assertSame('<tr><td colspan="3">Memuat jam loket…</td></tr>', trim($rows[1]));
        $this->assertStringNotContainsString('Jam contoh', $html);
    }

    #[TestWith(['/'])]
    #[TestWith(['/login'])]
    #[TestWith(['/layanan'])]
    public function test_serves_portal_pages_without_setting_any_cookie(string $uri): void
    {
        $response = $this->get($uri);

        $response->assertOk();
        $this->assertSame([], $response->headers->getCookies());
    }

    public function test_redirects_a_former_html_address_permanently_and_keeps_its_query_string(): void
    {
        $response = $this->get('/login.html?sesi=berakhir&lanjut=dashboard%2F%23%2Fberita');

        $response->assertMovedPermanently()->assertRedirect('/login?sesi=berakhir&lanjut=dashboard%2F%23%2Fberita');
    }

    public function test_redirects_index_html_to_the_home_page(): void
    {
        $response = $this->get('/index.html');

        $response->assertMovedPermanently()->assertRedirect('/');
    }

    #[TestWith(['/halaman-tidak-ada'])]
    #[TestWith(['/halaman-tidak-ada.html'])]
    #[TestWith(['/.env'])]
    #[TestWith(['/composer.json'])]
    #[TestWith(['/tidak/ada/halaman'])]
    #[TestWith(['/berita/judul/lain'])]
    public function test_renders_the_portal_404_page_with_addresses_from_the_site_root_for_any_other_address(string $uri): void
    {
        $response = $this->get($uri);

        $html = $response->assertNotFound()->getContent();
        $this->assertSame(PortalPage::fromSiteRoot(file_get_contents(resource_path('views/errors/404.blade.php'))), $html);
        $this->assertSame(0, preg_match('/\s(?:href|src)="(?![a-z][a-z0-9+.-]*:|\/|#)/i', $html));
        $this->assertStringContainsString('<link rel="stylesheet" href="/assets/css/components.css">', $html);
        $this->assertStringContainsString('<link rel="stylesheet" href="/tema-portal.css">', $html);
        $this->assertStringContainsString('<script src="/assets/js/main.js" defer></script>', $html);
    }

    public function test_answers_missing_api_addresses_and_json_requests_with_json(): void
    {
        $this->get('/api/tidak-ada')->assertNotFound()->assertHeader('Content-Type', 'application/json');
        $this->getJson('/tidak/ada')->assertNotFound()->assertHeader('Content-Type', 'application/json');
    }
}
