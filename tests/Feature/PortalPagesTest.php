<?php

namespace Tests\Feature;

use App\Models\Message;
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
