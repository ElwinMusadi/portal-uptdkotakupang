<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Enums\UploadTarget;
use App\Models\Setting;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[TestWith(['GET'])]
    #[TestWith(['PUT'])]
    public function test_returns_401_to_guests(string $method): void
    {
        $this->json($method, '/api/pengaturan/profil', $this->profile())->assertUnauthorized();
    }

    #[TestWith(['GET'])]
    #[TestWith(['PUT'])]
    public function test_forbids_employees_without_the_module_with_403(string $method): void
    {
        $employee = User::factory()->withModules(DashboardModule::VisiMisi)->create();

        $response = $this->actingAs($employee)->json($method, '/api/pengaturan/profil', $this->profile());

        $response->assertForbidden()->assertExactJson(['message' => 'Modul Tentang UPTD belum ditugaskan kepada Anda.']);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_shows_the_text_the_portal_showed_without_the_placeholder_name_until_first_saved(): void
    {
        $response = $this->actingAs($this->profilEmployee())->getJson('/api/pengaturan/profil');

        $response->assertOk()
            ->assertJsonPath('judul', 'Melayani pajak daerah.')
            ->assertJsonPath('lanjutan', 'Dari jantung Kota Kupang.')
            ->assertJsonPath('foto', null)
            ->assertJsonPath('kedudukan.judul', 'Bagian dari BPAD NTT.')
            ->assertJsonPath('identitas.*.label', ['Nama unit', 'Dikenal sebagai', 'Induk organisasi', 'Dasar organisasi', 'Susunan organisasi', 'Wilayah kerja', 'Mitra satu atap', 'Pajak yang dikelola', 'Kanal pembayaran'])
            ->assertJsonPath('tugasFungsi.*.kataKunci', ['Pungut', 'Tetapkan', 'Periksa', 'Layani', 'Jangkau', 'Awasi', 'Kelola'])
            ->assertJsonPath('sambutan', [
                'kutipan' => 'Kami ingin setiap warga datang sekali dan pulang dengan urusan selesai. PINTU adalah janji kami untuk informasi yang jelas, resmi, dan selalu terbaru.',
                'nama' => 'Remmy Christian Pah, S.STP, M.Si',
                'jabatan' => 'Kepala UPTD Pendapatan Daerah Wilayah Kota Kupang',
                'foto' => null,
            ]);
    }

    public function test_saves_the_document_and_logs_it(): void
    {
        $employee = $this->profilEmployee();
        $profile = $this->profile();
        $profile['lanjutan'] = null;
        $profile['foto'] = null;
        $profile['keteranganFoto'] = null;
        $profile['kedudukan']['deskripsi'] = null;
        $profile['tugasFungsi'][0]['kataKunci'] = null;
        $profile['sambutan']['nama'] = null;
        $profile['sambutan']['foto'] = null;

        $response = $this->actingAs($employee)->putJson('/api/pengaturan/profil', $profile);

        $response->assertOk()
            ->assertJsonPath('lanjutan', '')
            ->assertJsonPath('foto', null)
            ->assertJsonPath('keteranganFoto', '')
            ->assertJsonPath('kedudukan.deskripsi', '')
            ->assertJsonPath('tugasFungsi.0.kataKunci', '')
            ->assertJsonPath('sambutan', ['kutipan' => 'Sambutan baru.', 'nama' => '', 'jabatan' => 'Kepala UPTD', 'foto' => null]);
        $this->assertSame($response->json(), Setting::profile());
        $this->assertDatabaseHas('activities', [
            'user_id' => $employee->id,
            'module' => 'profil',
            'action' => 'memperbarui',
            'target' => 'Tentang UPTD',
        ]);
    }

    public function test_logs_nothing_when_the_same_document_is_saved_again(): void
    {
        $employee = $this->profilEmployee();
        $this->actingAs($employee)->putJson('/api/pengaturan/profil', $this->profile())->assertOk();

        $this->actingAs($employee)->putJson('/api/pengaturan/profil', $this->profile())->assertOk();

        $this->assertDatabaseCount('activities', 1);
    }

    public function test_keeps_uploaded_photos_and_deletes_the_ones_it_replaces(): void
    {
        Storage::fake('public');
        $employee = $this->profilEmployee();
        [$building, $head, $newBuilding] = Upload::factory()->count(3)->create(['tujuan' => UploadTarget::Profil])->all();
        foreach ([$building, $head, $newBuilding] as $upload) {
            Storage::disk('public')->put($upload->path, 'foto');
        }
        $profile = $this->profile();
        $profile['foto'] = $building->url();
        $profile['sambutan']['foto'] = $head->url();

        $this->actingAs($employee)->putJson('/api/pengaturan/profil', $profile)
            ->assertOk()
            ->assertJsonPath('foto', $building->url())
            ->assertJsonPath('sambutan.foto', $head->url());
        $this->assertNotNull($building->fresh()->dipakai_pada);
        $this->assertNotNull($head->fresh()->dipakai_pada);

        // Saving other changes keeps the photos still in use
        $profile['judul'] = 'Judul baru.';
        $this->actingAs($employee)->putJson('/api/pengaturan/profil', $profile)->assertOk();
        $this->assertModelExists($building);
        $this->assertModelExists($head);
        Storage::disk('public')->assertExists([$building->path, $head->path]);

        $profile['foto'] = $newBuilding->url();
        $profile['sambutan']['foto'] = null;
        $this->actingAs($employee)->putJson('/api/pengaturan/profil', $profile)->assertOk();

        $this->assertModelMissing($building);
        $this->assertModelMissing($head);
        Storage::disk('public')->assertMissing([$building->path, $head->path]);
        $this->assertNotNull($newBuilding->fresh()->dipakai_pada);
        Storage::disk('public')->assertExists($newBuilding->path);
    }

    #[TestWith(['foto', 'Foto kantor harus diunggah lewat dashboard.'])]
    #[TestWith(['sambutan.foto', 'Foto Kepala UPTD harus diunggah lewat dashboard.'])]
    public function test_rejects_photos_that_are_not_its_uploads(string $field, string $message): void
    {
        $news = Upload::factory()->create();

        foreach (['data:image/png;base64,iVBORw0KGgo=', 'https://example.com/foto.jpg', $news->url(), ['url' => $news->url()]] as $value) {
            $profile = $this->profile();
            data_set($profile, $field, $value);

            $this->actingAs($this->profilEmployee())->putJson('/api/pengaturan/profil', $profile)
                ->assertUnprocessable()
                ->assertJsonValidationErrors([$field => $message]);
        }
        $this->assertDatabaseCount('settings', 0);
        $this->assertNull($news->fresh()->dipakai_pada);
    }

    public function test_validates_the_opening_position_lists_and_welcome(): void
    {
        $profile = $this->profile();
        $profile['judul'] = '';
        $profile['pengantar'] = str_repeat('a', 401);
        $profile['kedudukan']['judul'] = '';
        $profile['identitas'][0]['nilai'] = '';
        $profile['identitas'][1]['id'] = $profile['identitas'][0]['id'];
        $profile['tugasFungsi'][0]['judul'] = '';
        $profile['sambutan']['kutipan'] = "Sambutan\u{0007}";

        $response = $this->actingAs($this->profilEmployee())->putJson('/api/pengaturan/profil', $profile);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'judul' => 'Judul wajib diisi.',
            'pengantar' => 'Pengantar maksimal 400 karakter.',
            'kedudukan.judul' => 'Judul kedudukan wajib diisi.',
            'identitas.0.nilai' => 'Isi wajib diisi.',
            'identitas.1.id' => 'Baris identitas tercantum dua kali.',
            'tugasFungsi.0.judul' => 'Tugas wajib diisi.',
            'sambutan.kutipan' => 'Kutipan sambutan berisi karakter kontrol yang tidak diizinkan.',
        ]);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_accepts_empty_lists_and_limits_them(): void
    {
        $employee = $this->profilEmployee();
        $this->actingAs($employee)->putJson('/api/pengaturan/profil', ['identitas' => [], 'tugasFungsi' => []] + $this->profile())
            ->assertOk()
            ->assertJsonPath('identitas', [])
            ->assertJsonPath('tugasFungsi', []);

        $profile = $this->profile();
        $profile['identitas'] = array_map(fn (int $index): array => ['id' => "i{$index}", 'label' => "Label {$index}", 'nilai' => 'Isi'], range(1, 13));

        $this->actingAs($employee)->putJson('/api/pengaturan/profil', $profile)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['identitas' => 'Maksimal 12 baris identitas.']);
    }

    private function profilEmployee(): User
    {
        return User::factory()->withModules(DashboardModule::Profil)->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function profile(): array
    {
        return [
            'judul' => 'Melayani pajak daerah.',
            'lanjutan' => 'Dari jantung Kota Kupang.',
            'pengantar' => 'Pengantar baru.',
            'foto' => null,
            'keteranganFoto' => 'Kantor Bersama Samsat Kota Kupang.',
            'kedudukan' => ['judul' => 'Bagian dari BPAD NTT.', 'lanjutan' => 'Dekat dengan warga kota.', 'deskripsi' => 'Deskripsi.'],
            'identitas' => [
                ['id' => 'i1', 'label' => 'Nama unit', 'nilai' => 'UPTD Pendapatan Daerah Wilayah Kota Kupang'],
                ['id' => 'i2', 'label' => 'Dikenal sebagai', 'nilai' => 'Samsat Kota Kupang'],
            ],
            'tugasFungsi' => [['id' => 't1', 'judul' => 'Pendataan objek pajak.', 'deskripsi' => 'Penjelasan.', 'kataKunci' => 'Data']],
            'sambutan' => ['kutipan' => 'Sambutan baru.', 'nama' => 'Nama Kepala', 'jabatan' => 'Kepala UPTD', 'foto' => null],
        ];
    }
}
