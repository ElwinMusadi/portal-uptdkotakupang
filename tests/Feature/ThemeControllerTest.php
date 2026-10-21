<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ThemeControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[TestWith(['GET', '/api/pengaturan/tema'])]
    #[TestWith(['PUT', '/api/pengaturan/tema'])]
    #[TestWith(['POST', '/api/pengaturan/tema/pratinjau'])]
    public function test_returns_401_to_guests(string $method, string $uri): void
    {
        $this->json($method, $uri, $this->theme())->assertUnauthorized();
    }

    public function test_lets_every_employee_read_the_built_in_look_until_first_saved(): void
    {
        $employee = User::factory()->withModules(DashboardModule::Faq)->create();

        $this->actingAs($employee)->getJson('/api/pengaturan/tema')
            ->assertOk()
            ->assertExactJson(['portal' => Setting::DEFAULT_PORTAL_THEME, 'dashboard' => Setting::DEFAULT_THEME_SECTION]);
    }

    #[TestWith(['PUT', '/api/pengaturan/tema'])]
    #[TestWith(['POST', '/api/pengaturan/tema/pratinjau'])]
    public function test_forbids_employees_other_than_administrators_to_save_or_preview_it(string $method, string $uri): void
    {
        $employee = User::factory()->withModules(...DashboardModule::cases())->create();

        $this->actingAs($employee)->json($method, $uri, $this->theme(['aksen' => '#2F7D4F']))
            ->assertForbidden()
            ->assertExactJson(['message' => 'Hanya Administrator yang dapat mengubah Tampilan.']);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_saves_both_parts_with_the_accent_in_capitals_and_without_unknown_keys(): void
    {
        $theme = $this->theme(['aksen' => '#2f7d4f', 'latar' => 'sejuk', 'hurufTeks' => 'inter', 'hurufJudul' => 'lexend', 'sudut' => 'tegas', 'tebalJudul' => 800, 'gelap' => '#0f1b3d', 'panel' => '#0c1839', 'navbar' => '#ffffff', 'kaca' => 'kuat', 'mode' => 'gelap', 'motif' => 'titik'], ['hurufTeks' => 'nunito-sans', 'tebalJudul' => 700]);
        $theme['portal']['css'] = 'body { display: none }';
        $theme['dashboard']['mode'] = 'gelap';
        $theme['dashboard']['motif'] = 'titik';
        $theme['dashboard']['panel'] = '#000000';
        $theme['dashboard']['navbar'] = '#FFFFFF';
        $theme['dashboard']['kaca'] = 'kuat';
        $theme['logo'] = 'merah';

        $response = $this->actingAs(User::factory()->administrator()->create())->putJson('/api/pengaturan/tema', $theme);

        $expected = [
            'portal' => ['aksen' => '#2F7D4F', 'latar' => 'sejuk', 'hurufTeks' => 'inter', 'hurufJudul' => 'lexend', 'sudut' => 'tegas', 'skala' => 100, 'tebalJudul' => 800, 'gelap' => '#0F1B3D', 'panel' => '#0C1839', 'navbar' => '#FFFFFF', 'kaca' => 'kuat', 'mode' => 'gelap', 'motif' => 'titik'],
            'dashboard' => [...Setting::DEFAULT_THEME_SECTION, 'hurufTeks' => 'nunito-sans', 'tebalJudul' => 700],
        ];
        $response->assertOk()->assertExactJson($expected);
        $this->assertSame($expected, Setting::query()->find(Setting::THEME)->value);
        $this->assertSame($expected, Setting::theme());
        $this->assertDatabaseCount('activities', 0);
    }

    /**
     * @param  array<string, mixed>  $portal
     */
    #[TestWith([['aksen' => '#2F7D4'], 'portal.aksen', 'Warna utama portal harus berupa kode warna #RRGGBB.'])]
    #[TestWith([['aksen' => 'red'], 'portal.aksen', 'Warna utama portal harus berupa kode warna #RRGGBB.'])]
    #[TestWith([['aksen' => '#2F7D4F;}'], 'portal.aksen', 'Warna utama portal harus berupa kode warna #RRGGBB.'])]
    #[TestWith([['latar' => 'gelap'], 'portal.latar', 'Nuansa latar portal tidak tersedia.'])]
    #[TestWith([['hurufTeks' => 'manrope'], 'portal.hurufTeks', 'Huruf teks portal tidak tersedia.'])]
    #[TestWith([['hurufTeks' => 'dm-sans'], 'portal.hurufTeks', 'Huruf teks portal tidak tersedia.'])]
    #[TestWith([['hurufTeks' => 'sama'], 'portal.hurufTeks', 'Huruf teks portal tidak tersedia.'])]
    #[TestWith([['hurufJudul' => 'comic-sans'], 'portal.hurufJudul', 'Huruf judul portal tidak tersedia.'])]
    #[TestWith([['sudut' => 'bulat'], 'portal.sudut', 'Kelengkungan sudut portal tidak tersedia.'])]
    #[TestWith([['sudut' => null], 'portal.sudut', 'Kelengkungan sudut portal wajib diisi.'])]
    #[TestWith([['skala' => 105], 'portal.skala', 'Skala tampilan portal tidak tersedia.'])]
    #[TestWith([['mode' => 'otomatis'], 'portal.mode', 'Mode tampilan portal tidak tersedia.'])]
    #[TestWith([['mode' => null], 'portal.mode', 'Mode tampilan portal wajib diisi.'])]
    #[TestWith([['skala' => 'besar'], 'portal.skala', 'Skala tampilan portal tidak valid.'])]
    #[TestWith([['tebalJudul' => 400], 'portal.tebalJudul', 'Ketebalan judul portal tidak tersedia.'])]
    #[TestWith([['gelap' => '#0F1B3'], 'portal.gelap', 'Warna gelap portal harus berupa kode warna #RRGGBB.'])]
    #[TestWith([['gelap' => 'hitam'], 'portal.gelap', 'Warna gelap portal harus berupa kode warna #RRGGBB.'])]
    #[TestWith([['gelap' => null], 'portal.gelap', 'Warna gelap portal wajib diisi.'])]
    #[TestWith([['panel' => '#0A0A0'], 'portal.panel', 'Warna panel gelap portal harus berupa kode warna #RRGGBB.'])]
    #[TestWith([['panel' => null], 'portal.panel', 'Warna panel gelap portal wajib diisi.'])]
    #[TestWith([['navbar' => 'putih'], 'portal.navbar', 'Warna navbar portal harus berupa kode warna #RRGGBB.'])]
    #[TestWith([['navbar' => '#FFF'], 'portal.navbar', 'Warna navbar portal harus berupa kode warna #RRGGBB.'])]
    #[TestWith([['navbar' => null], 'portal.navbar', 'Warna navbar portal wajib diisi.'])]
    #[TestWith([['kaca' => 'tebal'], 'portal.kaca', 'Efek glass navbar portal tidak tersedia.'])]
    #[TestWith([['kaca' => null], 'portal.kaca', 'Efek glass navbar portal wajib diisi.'])]
    #[TestWith([['motif' => 'garis'], 'portal.motif', 'Motif latar portal tidak tersedia.'])]
    #[TestWith([['motif' => null], 'portal.motif', 'Motif latar portal wajib diisi.'])]
    #[TestWith([['tebalJudul' => 650], 'portal.tebalJudul', 'Ketebalan judul portal tidak tersedia.'])]
    #[TestWith([['tebalJudul' => 'tebal'], 'portal.tebalJudul', 'Ketebalan judul portal tidak valid.'])]
    #[TestWith([['tebalJudul' => null], 'portal.tebalJudul', 'Ketebalan judul portal wajib diisi.'])]
    public function test_rejects_values_outside_the_lists(array $portal, string $field, string $message): void
    {
        $response = $this->actingAs(User::factory()->administrator()->create())->putJson('/api/pengaturan/tema', $this->theme($portal));

        $response->assertUnprocessable()->assertJsonValidationErrors([$field => $message]);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_requires_both_parts(): void
    {
        $theme = $this->theme();
        unset($theme['dashboard']);

        $this->actingAs(User::factory()->administrator()->create())->putJson('/api/pengaturan/tema', $theme)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['dashboard' => 'Tema dashboard wajib diisi.']);
    }

    public function test_previews_an_unsaved_theme_without_saving_it(): void
    {
        $theme = $this->theme(['aksen' => '#2F7D4F', 'hurufTeks' => 'inter', 'gelap' => '#2B63F6', 'panel' => '#2B63F6', 'navbar' => '#FFFFFF', 'kaca' => 'halus'], ['aksen' => '#F2C46B', 'sudut' => 'membulat', 'gelap' => '#9F1239']);

        $response = $this->actingAs(User::factory()->administrator()->create())->postJson('/api/pengaturan/tema/pratinjau', $theme);

        $response->assertOk()
            ->assertJsonPath('portal.variabel.--accent', '#2F7D4F')
            ->assertJsonPath('portal.variabel.--paper', '#F7F6F2')
            ->assertJsonPath('portal.variabel.--r-scale', '1')
            ->assertJsonPath('portal.kontras.0.pasangan', 'Teks putih pada tombol aksen')
            ->assertJsonPath('portal.gelap', '#082163')
            ->assertJsonPath('portal.panel', '#010F51')
            ->assertJsonPath('portal.navbar', '#FFFFFF')
            ->assertJsonPath('portal.tutupanKaca', 0.9)
            ->assertJsonPath('portal.variabel.--nav-tint', '15, 19, 25')
            ->assertJsonPath('dashboard.gelap', '#520018')
            ->assertJsonPath('portal.variabel.--night', '#010F51')
            ->assertJsonPath('portal.variabel.--ink-fill', '#082163')
            ->assertJsonPath('dashboard.aksen', '#936A00')
            ->assertJsonPath('dashboard.gelap', '#520018')
            ->assertJsonPath('dashboard.kontras.0.pasangan', 'Teks putih pada tombol warna merek');
        $this->assertStringContainsString('src: url("/assets/fonts/inter/inter-latin-wght-normal.woff2")', $response->json('portal.huruf'));
        $this->assertStringContainsString('--radius: 0.84375rem;', $response->json('dashboard.css'));
        $this->assertDatabaseCount('settings', 0);
    }

    /**
     * A complete theme document: the built-in look unless overridden.
     *
     * @param  array<string, mixed>  $portal
     * @param  array<string, mixed>  $dashboard
     * @return array<string, mixed>
     */
    private function theme(array $portal = [], array $dashboard = []): array
    {
        return [
            'portal' => [...Setting::DEFAULT_PORTAL_THEME, ...$portal],
            'dashboard' => [...Setting::DEFAULT_THEME_SECTION, ...$dashboard],
        ];
    }
}
