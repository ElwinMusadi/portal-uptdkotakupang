<?php

namespace Tests\Feature;

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
