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
        $site['bilahPengumuman'] = ['aktif' => false, 'teks' => null, 'tautanLabel' => null, 'tautanUrl' => null, 'warna' => 'merah'];
        $site['logo'] = 'x.png';

        $response = $this->actingAs($administrator)->putJson('/api/pengaturan/situs', $site);

        $response->assertOk()
            ->assertJsonPath('namaPortal', 'PINTU Kupang')
            ->assertJsonPath('kepanjangan', '')
            ->assertJsonPath('hakCipta', '')
            ->assertJsonPath('bilahPengumuman', ['aktif' => false, 'teks' => '', 'tautanLabel' => '', 'tautanUrl' => '']);
        $this->assertArrayNotHasKey('logo', $response->json());
        $this->assertSame($response->json(), Setting::site());
        $this->assertDatabaseCount('activities', 0);
        $this->actingAs(User::factory()->create())->getJson('/api/pengaturan')->assertOk()->assertJsonStructure(['situs']);
    }

    public function test_stores_the_announcement_switch_as_a_boolean(): void
    {
        $site = Setting::INITIAL_SITE;
        $site['bilahPengumuman']['aktif'] = 0;

        $this->actingAs(User::factory()->administrator()->create())->putJson('/api/pengaturan/situs', $site)
            ->assertOk()
            ->assertJsonPath('bilahPengumuman.aktif', false);
        $this->assertFalse(Setting::site()['bilahPengumuman']['aktif']);
    }

    public function test_validates_the_document(): void
    {
        $administrator = User::factory()->administrator()->create();
        $site = Setting::INITIAL_SITE;
        $site['namaPortal'] = '';
        $site['namaInstansi'] = str_repeat('a', 121);
        $site['urlPortal'] = 'javascript:alert(1)';
        $site['deskripsi'] = "Deskripsi\u{0007}";
        $site['bilahPengumuman'] = ['aktif' => true, 'teks' => '', 'tautanLabel' => str_repeat('a', 31), 'tautanUrl' => 'data:text/html,x'];

        $this->actingAs($administrator)->putJson('/api/pengaturan/situs', $site)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'namaPortal' => 'Nama portal wajib diisi.',
                'namaInstansi' => 'Nama instansi maksimal 120 karakter.',
                'urlPortal' => 'Alamat portal publik: gunakan alamat lengkap (https://…) atau nama halaman portal (mis. jadwal.html).',
                'deskripsi' => 'Deskripsi singkat berisi karakter kontrol yang tidak diizinkan.',
                'bilahPengumuman.teks' => 'Teks pengumuman wajib diisi selama bilah pengumuman ditampilkan.',
                'bilahPengumuman.tautanLabel' => 'Label tautan maksimal 30 karakter.',
                'bilahPengumuman.tautanUrl' => 'Tujuan tautan: gunakan alamat lengkap (https://…) atau nama halaman portal (mis. jadwal.html).',
            ]);
        $this->actingAs($administrator)->putJson('/api/pengaturan/situs', ['bilahPengumuman' => ['aktif' => 'ya']] + Setting::INITIAL_SITE)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['bilahPengumuman.aktif' => 'Pilihan tampilkan bilah pengumuman tidak valid.']);
        $this->assertDatabaseCount('settings', 0);
    }
}
