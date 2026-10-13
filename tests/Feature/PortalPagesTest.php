<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\Setting;
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
            'jadwal' => ['/jadwal', 'jadwal'],
            'informasi' => ['/informasi', 'informasi'],
            'berita detail' => ['/berita-detail', 'berita-detail'],
            'dokumentasi' => ['/dokumentasi', 'dokumentasi'],
            'unduhan' => ['/unduhan', 'unduhan'],
            'kontak' => ['/kontak', 'kontak'],
            'login' => ['/login', 'login'],
            'dashboard pegawai' => ['/dashboard-pegawai', 'dashboard-pegawai'],
            'design system' => ['/design-system', 'design-system'],
            'kebijakan privasi' => ['/kebijakan-privasi', 'kebijakan-privasi'],
            'syarat ketentuan' => ['/syarat-ketentuan', 'syarat-ketentuan'],
        ];
    }

    #[DataProvider('pages')]
    public function test_serves_each_portal_page_byte_for_byte_as_written(string $uri, string $view): void
    {
        $response = $this->get($uri);

        $response->assertOk()->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        $this->assertSame(file_get_contents(resource_path("views/portal/{$view}.blade.php")), $response->getContent());
    }

    public function test_leaves_the_home_page_faq_entries_to_the_public_api(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(1, preg_match('/<section[^>]*\bid="faq"[^>]*>.*?<\/section>/s', $html, $faq));
        $outsideTemplate = preg_replace('/<template data-faq-item>.*?<\/template>/s', '', $faq[0]);
        $this->assertStringContainsString('data-faq-list', $outsideTemplate);
        $this->assertStringNotContainsString('<details', $outsideTemplate);
    }

    public function test_leaves_the_samsat_keliling_schedule_to_the_public_api(): void
    {
        $home = $this->get('/')->assertOk()->getContent();
        $schedule = $this->get('/jadwal')->assertOk()->getContent();

        $this->assertSame(1, preg_match('/<ul[^>]*data-keliling-today>(.*?)<\/ul>/s', $home, $today));
        $this->assertSame(1, preg_match('/<div[^>]*data-keliling-week[^>]*>(.*?)<\/div>/s', $schedule, $week));
        $this->assertStringNotContainsString('Kantor Kelurahan', $today[1]);
        $this->assertStringContainsString('Memuat jadwal', $today[1]);
        $this->assertSame('<p class="muted" role="status">Memuat jadwal minggu ini…</p>', trim($week[1]));
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
    public function test_leaves_the_tax_simulation_rates_to_the_public_api(string $uri): void
    {
        $html = $this->get($uri)->assertOk()->getContent();

        $this->assertSame(1, preg_match('/<section[^>]*\bid="simulasi"[^>]*>.*?<\/section>/s', $html, $simulation));
        foreach (['66%', '1%', 'maks. 24', '285.000', 'Rp35.000', 'Rp143.000', 'Asumsi contoh'] as $exampleValue) {
            $this->assertStringNotContainsString($exampleValue, $simulation[0]);
        }
        $this->assertStringContainsString('<p class="tile__label" data-tarif="label-opsen">Opsen PKB</p>', $simulation[0]);
        $this->assertStringContainsString('<span data-tarif="maks"></span>', $simulation[0]);
        $this->assertStringContainsString('data-sim-catatan', $simulation[0]);
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
            $this->assertMatchesRegularExpression('/<ul role="list" data-kontak-hubungi>\s*<li><a href="kontak.html#pengaduan">Kanal pengaduan<\/a><\/li>\s*<\/ul>/', $html, $view);
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

    public function test_keeps_the_initial_homepage_texts_in_the_markup_for_the_public_api_to_replace(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $text = preg_replace('/[ \t\r\n]+/', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        $initial = Setting::INITIAL_HOMEPAGE;
        array_walk_recursive($initial, function (mixed $value, string|int $key) use ($text): void {
            if (is_string($value) && $key !== 'id' && $key !== 'tombolUrl') {
                $this->assertStringContainsString($value, $text);
            }
        });
        foreach (['data-beranda-hero-judul', 'data-beranda-tombol', 'data-beranda-catatan ', 'data-beranda-nilai', 'data-beranda-mitra', 'data-beranda-kanal', 'data-beranda-catatan-notis', 'data-beranda-wajib', 'data-beranda-jaminan'] as $hook) {
            $this->assertStringContainsString($hook, $html);
        }
        $this->assertSame(4, substr_count($html, 'data-beranda-judul="'));
        $this->assertSame(6, substr_count($html, 'data-beranda-teks="'));
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
    public function test_renders_the_portal_404_page_for_any_other_address(string $uri): void
    {
        $response = $this->get($uri);

        $response->assertNotFound();
        $this->assertSame(file_get_contents(resource_path('views/errors/404.blade.php')), $response->getContent());
    }
}
