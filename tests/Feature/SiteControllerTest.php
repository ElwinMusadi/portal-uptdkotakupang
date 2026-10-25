<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class SiteControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[TestWith(['GET'])]
    #[TestWith(['PUT'])]
    public function test_returns_401_to_guests(string $method): void
    {
        $this->json($method, '/api/pengaturan/situs', Setting::INITIAL_SITE)->assertUnauthorized();
    }

    public function test_lets_every_employee_read_the_identity_in_the_portal_markup_until_first_saved(): void
    {
        $employee = User::factory()->withModules(DashboardModule::Faq)->create();

        $this->actingAs($employee)->getJson('/api/pengaturan/situs')
            ->assertOk()
            ->assertExactJson(Setting::INITIAL_SITE);
    }

    public function test_forbids_employees_other_than_administrators_to_save_it(): void
    {
        $employee = User::factory()->withModules(...DashboardModule::cases())->create();
        $site = Setting::INITIAL_SITE;
        $site['namaPortal'] = 'PORTAL';

        $this->actingAs($employee)->putJson('/api/pengaturan/situs', $site)
            ->assertForbidden()
            ->assertExactJson(['message' => 'Hanya Administrator yang dapat mengubah Pengaturan Situs.']);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_saves_the_document_without_an_activity_entry(): void
    {
        $administrator = User::factory()->administrator()->create();
        $site = Setting::INITIAL_SITE;
        $site['namaPortal'] = 'PINTU Kupang';
        $site['kepanjangan'] = null;
        $site['hakCipta'] = null;
        // A dashboard opened before D-082 still sends the announcement bar; it belongs to Berita & Pengumuman now
        $site['bilahPengumuman'] = ['aktif' => false, 'teks' => 'Bilah lama', 'tautanLabel' => null, 'tautanUrl' => null];
        $site['logo'] = 'x.png';

        $response = $this->actingAs($administrator)->putJson('/api/pengaturan/situs', $site);

        $response->assertOk()
            ->assertJsonPath('namaPortal', 'PINTU Kupang')
            ->assertJsonPath('kepanjangan', '')
            ->assertJsonPath('hakCipta', '');
        $this->assertArrayNotHasKey('logo', $response->json());
        $this->assertArrayNotHasKey('bilahPengumuman', $response->json());
        $this->assertSame(Setting::INITIAL_ANNOUNCEMENT_BAR, Setting::announcementBar());
        $this->assertSame($response->json(), Setting::site());
        $this->assertDatabaseCount('activities', 0);
        $this->actingAs(User::factory()->create())->getJson('/api/pengaturan')->assertOk()->assertJsonStructure(['situs']);
    }

    public function test_validates_the_document(): void
    {
        $administrator = User::factory()->administrator()->create();
        $site = Setting::INITIAL_SITE;
        $site['namaPortal'] = '';
        $site['namaInstansi'] = str_repeat('a', 121);
        $site['urlPortal'] = 'javascript:alert(1)';
        $site['deskripsi'] = "Deskripsi\u{0007}";

        $this->actingAs($administrator)->putJson('/api/pengaturan/situs', $site)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'namaPortal' => 'Nama portal wajib diisi.',
                'namaInstansi' => 'Nama instansi maksimal 120 karakter.',
                'urlPortal' => 'Alamat portal publik: gunakan alamat lengkap (https://…) atau nama halaman portal (mis. jadwal.html).',
                'deskripsi' => 'Deskripsi singkat berisi karakter kontrol yang tidak diizinkan.',
            ]);
        $this->assertDatabaseCount('settings', 0);
    }
}
