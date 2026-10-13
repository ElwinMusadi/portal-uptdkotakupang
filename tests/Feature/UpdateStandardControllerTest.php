<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\UpdateStandard;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class UpdateStandardControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_401_to_guests(): void
    {
        $this->getJson('/api/koleksi/pemutakhiran')->assertUnauthorized();
        $this->postJson('/api/koleksi/pemutakhiran', $this->standard())->assertUnauthorized();
    }

    public function test_lists_the_initial_standards_in_order_for_managers_of_the_module(): void
    {
        $response = $this->actingAs($this->pemutakhiranEmployee())->getJson('/api/koleksi/pemutakhiran');

        $response->assertOk()
            ->assertJsonPath('*.modul', ['papan', 'keliling', 'dokumentasi', 'berita', 'jam', 'layanan', 'dokumen', 'profil'])
            ->assertJsonPath('*.intervalHari', [1, 7, 14, 15, 30, 90, 180, 365])
            ->assertJsonPath('1.konten', 'Jadwal Samsat Keliling')
            ->assertJsonPath('1.unit', 'Seksi Penetapan dan Penagihan')
            ->assertJsonPath('1.frekuensi', 'Mingguan, setiap Jumat')
            ->assertJsonPath('5.frekuensi', 'Saat regulasi berubah · tinjau tiap 3 bulan');
        $this->assertSame(['id', 'konten', 'modul', 'unit', 'frekuensi', 'intervalHari', 'urutan', 'dibuat', 'diperbarui'], array_keys($response->json('0')));
        $this->actingAs(User::factory()->administrator()->create())->getJson('/api/koleksi/pemutakhiran')->assertOk()->assertJsonCount(8);
    }

    public function test_lists_other_employees_only_the_standards_of_their_modules(): void
    {
        $employee = User::factory()->withModules(DashboardModule::Keliling, DashboardModule::Berita)->create();

        $this->actingAs($employee)->getJson('/api/koleksi/pemutakhiran')
            ->assertOk()
            ->assertJsonPath('*.modul', ['keliling', 'berita']);
        $this->actingAs(User::factory()->withModules(DashboardModule::Faq)->create())->getJson('/api/koleksi/pemutakhiran')
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_lets_other_employees_read_only_the_standards_of_their_modules(): void
    {
        $employee = User::factory()->withModules(DashboardModule::Keliling)->create();

        $this->actingAs($employee)->getJson("/api/koleksi/pemutakhiran/{$this->standardOf('keliling')->id}")
            ->assertOk()
            ->assertJsonPath('modul', 'keliling');
        $this->actingAs($employee)->getJson("/api/koleksi/pemutakhiran/{$this->standardOf('berita')->id}")
            ->assertForbidden()
            ->assertExactJson(['message' => 'Modul Jadwal Pemutakhiran belum ditugaskan kepada Anda.']);
    }

    public function test_forbids_other_employees_to_change_standards_even_of_their_modules(): void
    {
        $employee = User::factory()->withModules(DashboardModule::Keliling)->create();
        $standard = $this->standardOf('keliling');
        $forbidden = ['message' => 'Modul Jadwal Pemutakhiran belum ditugaskan kepada Anda.'];

        $this->actingAs($employee)->postJson('/api/koleksi/pemutakhiran', $this->standard())->assertForbidden()->assertExactJson($forbidden);
        $this->actingAs($employee)->patchJson("/api/koleksi/pemutakhiran/{$standard->id}", ['intervalHari' => 3])->assertForbidden()->assertExactJson($forbidden);
        $this->actingAs($employee)->putJson('/api/koleksi/pemutakhiran/urutan', ['ids' => [$standard->id]])->assertForbidden()->assertExactJson($forbidden);
        $this->actingAs($employee)->postJson('/api/koleksi/pemutakhiran/hapus', ['ids' => [$standard->id]])->assertForbidden()->assertExactJson($forbidden);

        $this->assertSame(7, $standard->fresh()->interval_hari);
        $this->assertDatabaseCount('update_standards', 8);
        $this->assertDatabaseCount('activities', 0);
    }

    public function test_adds_a_standard_at_the_end_and_logs_it(): void
    {
        $employee = $this->pemutakhiranEmployee();

        $response = $this->actingAs($employee)->postJson('/api/koleksi/pemutakhiran', $this->standard() + ['urutan' => 0, 'id' => 'abc']);

        $response->assertCreated()
            ->assertJsonPath('konten', 'Pengumuman')
            ->assertJsonPath('modul', 'berita')
            ->assertJsonPath('unit', 'Semua unit')
            ->assertJsonPath('frekuensi', 'Saat ada kebijakan baru')
            ->assertJsonPath('intervalHari', 3)
            ->assertJsonPath('urutan', 8);
        $this->assertNotSame('abc', $response->json('id'));
        $this->assertDatabaseHas('activities', ['user_id' => $employee->id, 'module' => 'pemutakhiran', 'action' => 'menambahkan', 'target' => 'Pengumuman']);
    }

    public function test_validates_a_new_standard(): void
    {
        $employee = $this->pemutakhiranEmployee();
        $long = str_repeat('a', UpdateStandard::MAX_TEXT_LENGTH + 1);

        $this->actingAs($employee)->postJson('/api/koleksi/pemutakhiran', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'konten' => 'Nama konten wajib diisi.',
                'modul' => 'Pilih modul yang dipantau.',
                'unit' => 'Penanggung jawab wajib diisi.',
                'frekuensi' => 'Frekuensi wajib diisi.',
                'intervalHari' => 'Batas hari wajib diisi.',
            ]);
        $this->actingAs($employee)->postJson('/api/koleksi/pemutakhiran', ['konten' => $long, 'unit' => $long, 'frekuensi' => "Mingguan\u{0007}", 'modul' => 'faq', 'intervalHari' => 1.5])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'konten' => 'Nama konten maksimal 120 karakter.',
                'unit' => 'Penanggung jawab maksimal 120 karakter.',
                'frekuensi' => 'Frekuensi berisi karakter kontrol yang tidak diizinkan.',
                'modul' => 'Modul ini tidak dapat dipantau.',
                'intervalHari' => 'Batas hari harus bilangan bulat.',
            ]);
        $this->assertDatabaseCount('update_standards', 8);
    }

    #[TestWith(['modul', 'pemutakhiran', 'Modul ini tidak dapat dipantau.'])]
    #[TestWith(['modul', 'Berita', 'Modul ini tidak dapat dipantau.'])]
    #[TestWith(['intervalHari', 0, 'Batas hari antara 1 dan 730.'])]
    #[TestWith(['intervalHari', 731, 'Batas hari antara 1 dan 730.'])]
    #[TestWith(['intervalHari', 'tiga', 'Batas hari harus bilangan bulat.'])]
    #[TestWith(['konten', '   ', 'Nama konten wajib diisi.'])]
    public function test_rejects_an_invalid_field(string $field, mixed $value, string $message): void
    {
        $this->actingAs($this->pemutakhiranEmployee())->postJson('/api/koleksi/pemutakhiran', [$field => $value] + $this->standard())
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$field => $message]);
    }

    public function test_changes_a_standard_and_logs_only_real_changes(): void
    {
        $employee = $this->pemutakhiranEmployee();
        $standard = $this->standardOf('keliling');

        $this->actingAs($employee)->patchJson("/api/koleksi/pemutakhiran/{$standard->id}", ['modul' => 'jam', 'intervalHari' => '3'])
            ->assertOk()
            ->assertJsonPath('modul', 'jam')
            ->assertJsonPath('intervalHari', 3)
            ->assertJsonPath('konten', 'Jadwal Samsat Keliling');
        $this->actingAs($employee)->patchJson("/api/koleksi/pemutakhiran/{$standard->id}", ['modul' => 'jam', 'intervalHari' => 3])->assertOk();
        $this->actingAs($employee)->patchJson("/api/koleksi/pemutakhiran/{$standard->id}", ['intervalHari' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['intervalHari' => 'Batas hari antara 1 dan 730.']);

        $this->assertSame(3, $standard->fresh()->interval_hari);
        $this->assertDatabaseCount('activities', 1);
        $this->assertDatabaseHas('activities', ['module' => 'pemutakhiran', 'action' => 'memperbarui', 'target' => 'Jadwal Samsat Keliling']);
    }

    public function test_reorders_the_standards(): void
    {
        $employee = $this->pemutakhiranEmployee();
        $profil = $this->standardOf('profil');
        $dokumen = $this->standardOf('dokumen');

        $this->actingAs($employee)->putJson('/api/koleksi/pemutakhiran/urutan', ['ids' => [$profil->id, $dokumen->id]])->assertNoContent();

        $this->actingAs($employee)->getJson('/api/koleksi/pemutakhiran')
            ->assertJsonPath('*.modul', ['profil', 'dokumen', 'papan', 'keliling', 'dokumentasi', 'berita', 'jam', 'layanan'])
            ->assertJsonPath('*.urutan', [0, 1, 2, 3, 4, 5, 6, 7]);
        $this->assertDatabaseHas('activities', ['module' => 'pemutakhiran', 'action' => 'mengubah urutan', 'target' => '2 entri']);
        $this->actingAs($employee)->putJson('/api/koleksi/pemutakhiran/urutan', ['ids' => [(string) str()->ulid()]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ids' => 'Urutan tidak cocok dengan data terbaru. Muat ulang halaman lalu coba lagi.']);
    }

    public function test_lets_managers_of_the_module_delete_standards(): void
    {
        $employee = $this->pemutakhiranEmployee();
        $standard = $this->standardOf('profil');

        $this->actingAs($employee)->postJson('/api/koleksi/pemutakhiran/hapus', ['ids' => [$standard->id]])->assertNoContent();

        $this->assertModelMissing($standard);
        $this->assertDatabaseCount('update_standards', 7);
        $this->assertDatabaseHas('activities', ['user_id' => $employee->id, 'module' => 'pemutakhiran', 'action' => 'menghapus', 'target' => 'Profil instansi']);
    }

    /**
     * @return array<string, string|int>
     */
    private function standard(): array
    {
        return ['konten' => 'Pengumuman', 'modul' => 'berita', 'unit' => 'Semua unit', 'frekuensi' => 'Saat ada kebijakan baru', 'intervalHari' => 3];
    }

    private function standardOf(string $module): UpdateStandard
    {
        return UpdateStandard::query()->where('modul', $module)->firstOrFail();
    }

    private function pemutakhiranEmployee(): User
    {
        return User::factory()->withModules(DashboardModule::Pemutakhiran)->create();
    }
}
