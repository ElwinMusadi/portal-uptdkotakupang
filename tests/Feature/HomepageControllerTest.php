<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class HomepageControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[TestWith(['GET'])]
    #[TestWith(['PUT'])]
    public function test_returns_401_to_guests(string $method): void
    {
        $this->json($method, '/api/pengaturan/beranda', Setting::INITIAL_HOMEPAGE)->assertUnauthorized();
    }

    #[TestWith(['GET'])]
    #[TestWith(['PUT'])]
    public function test_forbids_employees_without_the_module_with_403(string $method): void
    {
        $employee = User::factory()->withModules(DashboardModule::Profil)->create();

        $response = $this->actingAs($employee)->json($method, '/api/pengaturan/beranda', Setting::INITIAL_HOMEPAGE);

        $response->assertForbidden()->assertExactJson(['message' => 'Modul Beranda belum ditugaskan kepada Anda.']);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_shows_the_text_of_the_homepage_markup_until_first_saved(): void
    {
        $response = $this->actingAs($this->berandaEmployee())->getJson('/api/pengaturan/beranda');

        $response->assertOk()
            ->assertJsonPath('hero.judul', 'Semua informasi Samsat.')
            ->assertJsonPath('hero.tombolUrl', 'layanan.html')
            ->assertJsonPath('nilai.*.judul', ['Persyaratan jelas.', 'Jadwal terbaru.', 'Tanpa perantara.'])
            ->assertJsonPath('mitra.*.singkatan', ['BP', 'PL', 'JR', 'BN', 'QR', 'SG'])
            ->assertJsonPath('kanal.*.kelompok', ['Kantor', 'Keliling', 'Digital', 'Informasi', 'Pengaduan'])
            ->assertJsonPath('kanal.0.item.1', "Loket 5\u{00A0}tahunan")
            ->assertJsonPath('biaya.jaminan', ['Bukti bayar resmi', 'Bisa non-tunai', 'Tercatat di sistem'])
            ->assertJsonPath('penutup.lanjutan', 'Asal jangan lupa dibawa.');
    }

    public function test_saves_the_document_and_logs_it(): void
    {
        $employee = $this->berandaEmployee();
        $homepage = Setting::INITIAL_HOMEPAGE;
        $homepage['hero']['judul'] = 'Judul baru.';
        $homepage['hero']['catatan'] = null;
        $homepage['nilai'][0]['deskripsi'] = null;
        $homepage['mitra'][0]['singkatan'] = null;
        $homepage['kanal'][0]['item'] = ['Loket pajak tahunan', ''];
        $homepage['biaya']['jaminan'] = ['Bukti bayar resmi', ''];
        $homepage['penutup']['lanjutan'] = null;

        $response = $this->actingAs($employee)->putJson('/api/pengaturan/beranda', $homepage);

        $response->assertOk()
            ->assertJsonPath('hero.judul', 'Judul baru.')
            ->assertJsonPath('hero.catatan', '')
            ->assertJsonPath('nilai.0.deskripsi', '')
            ->assertJsonPath('mitra.0.singkatan', '')
            ->assertJsonPath('kanal.0.item', ['Loket pajak tahunan'])
            ->assertJsonPath('biaya.jaminan', ['Bukti bayar resmi'])
            ->assertJsonPath('penutup.lanjutan', '');
        $this->assertSame($response->json(), Setting::homepage());
        $this->assertDatabaseHas('activities', ['user_id' => $employee->id, 'module' => 'beranda', 'action' => 'memperbarui', 'target' => 'Beranda']);
    }

    public function test_logs_nothing_when_the_same_document_is_saved_again(): void
    {
        $employee = $this->berandaEmployee();
        $this->actingAs($employee)->putJson('/api/pengaturan/beranda', Setting::INITIAL_HOMEPAGE)->assertOk();

        $this->actingAs($employee)->putJson('/api/pengaturan/beranda', Setting::INITIAL_HOMEPAGE)->assertOk();

        $this->assertDatabaseCount('activities', 1);
    }

    public function test_validates_the_opening_lists_and_sections(): void
    {
        $homepage = Setting::INITIAL_HOMEPAGE;
        $homepage['hero']['judul'] = '';
        $homepage['hero']['tombolUrl'] = 'javascript:alert(1)';
        $homepage['mitra'][0]['singkatan'] = 'BPAD';
        $homepage['kanal'][1]['item'] = array_fill(0, 9, 'Kanal');
        $homepage['penutup']['judul'] = '';
        $homepage['biaya']['deskripsi'] = "Biaya\u{0007}";

        $response = $this->actingAs($this->berandaEmployee())->putJson('/api/pengaturan/beranda', $homepage);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'hero.judul' => 'Judul pembuka wajib diisi.',
            'hero.tombolUrl' => 'Tautan tombol: gunakan alamat lengkap (https://…) atau nama halaman portal (mis. jadwal.html).',
            'mitra.0.singkatan' => 'Singkatan mitra maksimal 3 karakter.',
            'kanal.1.item' => 'Maksimal 8 kanal per kelompok.',
            'penutup.judul' => 'Judul penutup wajib diisi.',
            'biaya.deskripsi' => 'Deskripsi biaya berisi karakter kontrol yang tidak diizinkan.',
        ]);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_limits_the_lists_and_rejects_duplicate_items(): void
    {
        $homepage = Setting::INITIAL_HOMEPAGE;
        $homepage['nilai'] = array_map(fn (int $index): array => ['id' => "n{$index}", 'judul' => "Nilai {$index}.", 'deskripsi' => ''], range(1, 7));
        $homepage['mitra'][1]['id'] = $homepage['mitra'][0]['id'];

        $response = $this->actingAs($this->berandaEmployee())->putJson('/api/pengaturan/beranda', $homepage);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'nilai' => 'Maksimal 6 nilai utama.',
            'mitra.1.id' => 'Mitra tercantum dua kali.',
        ]);
    }

    private function berandaEmployee(): User
    {
        return User::factory()->withModules(DashboardModule::Beranda)->create();
    }
}
