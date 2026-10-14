<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Enums\UploadTarget;
use App\Enums\WorkflowStatus;
use App\Models\Documentation;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class DocumentationControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_401_to_guests(): void
    {
        $this->getJson('/api/koleksi/dokumentasi')->assertUnauthorized();
        $this->postJson('/api/koleksi/dokumentasi', $this->activity())->assertUnauthorized();
    }

    #[TestWith(['GET', '/api/koleksi/dokumentasi'])]
    #[TestWith(['POST', '/api/koleksi/dokumentasi'])]
    public function test_forbids_employees_without_the_module_with_403(string $method, string $uri): void
    {
        $employee = User::factory()->withModules(DashboardModule::Berita)->create();

        $response = $this->actingAs($employee)->json($method, $uri, $this->activity());

        $response->assertForbidden()->assertExactJson(['message' => 'Modul Dokumentasi Kegiatan belum ditugaskan kepada Anda.']);
    }

    public function test_lets_update_schedule_managers_list_but_not_read_or_change_activities(): void
    {
        $employee = User::factory()->withModules(DashboardModule::Pemutakhiran)->create();
        $activity = Documentation::query()->firstOrFail();

        $this->actingAs($employee)->getJson('/api/koleksi/dokumentasi')->assertOk()->assertJsonCount(9);
        $this->actingAs($employee)->getJson("/api/koleksi/dokumentasi/{$activity->id}")->assertForbidden();
        $this->actingAs($employee)->patchJson("/api/koleksi/dokumentasi/{$activity->id}", ['lokasi' => 'Lain'])->assertForbidden();
    }

    public function test_lists_the_example_activities_as_drafts_without_photos_newest_first(): void
    {
        $response = $this->actingAs($this->dokumentasiEmployee())->getJson('/api/koleksi/dokumentasi');

        $response->assertOk()
            ->assertJsonCount(9)
            ->assertJsonPath('0.judul', 'Uji coba portal PINTU bersama petugas loket.')
            ->assertJsonPath('0.tanggal', '2026-11-18')
            ->assertJsonPath('*.tanggal', ['2026-11-18', '2026-11-04', '2026-10-21', '2026-10-15', '2026-10-14', '2026-10-08', '2026-10-06', '2026-10-02', '2026-10-01'])
            ->assertJsonPath('*.status', array_fill(0, 9, 'draf'))
            ->assertJsonPath('*.foto', array_fill(0, 9, []))
            ->assertJsonPath('3.judul', 'Operasi gabungan pemeriksaan pajak kendaraan.')
            ->assertJsonPath('3.kategori', 'operasi')
            ->assertJsonPath('3.lokasi', 'Jl. El Tari')
            ->assertJsonPath('3.deskripsi', 'Operasi gabungan bersama Satlantas dan Jasa Raharja.')
            ->assertJsonPath('3.penyedia', 'Seksi Penetapan dan Penagihan')
            ->assertJsonPath('3.pemeriksa', 'Kepala Seksi Penetapan dan Penagihan')
            ->assertJsonPath('0.riwayat.0.oleh', 'PINTU')
            ->assertJsonPath('0.riwayat.0.aksi', 'memasukkan data awal dari halaman Dokumentasi');
    }

    public function test_adds_a_draft_with_its_photos_whatever_workflow_fields_are_sent(): void
    {
        $employee = $this->dokumentasiEmployee();
        [$first, $second] = [$this->photo(), $this->photo()];

        $response = $this->actingAs($employee)->postJson('/api/koleksi/dokumentasi', [
            'status' => 'terbit',
            'riwayat' => [['waktu' => '2026-01-01T00:00:00Z', 'oleh' => 'Orang lain', 'aksi' => 'menerbitkan', 'ke' => 'terbit']],
            'foto' => [
                ['id' => 'a', 'src' => $first->url(), 'keterangan' => 'Antrean warga.'],
                ['id' => 'b', 'src' => $second->url(), 'keterangan' => null],
            ],
        ] + $this->activity());

        $response->assertCreated()
            ->assertJsonPath('judul', 'Samsat Keliling di Kelurahan Oebobo.')
            ->assertJsonPath('tanggal', '2026-10-13')
            ->assertJsonPath('foto', [
                ['id' => 'a', 'src' => $first->url(), 'keterangan' => 'Antrean warga.'],
                ['id' => 'b', 'src' => $second->url(), 'keterangan' => ''],
            ])
            ->assertJsonPath('status', 'draf')
            ->assertJsonCount(1, 'riwayat')
            ->assertJsonPath('riwayat.0.oleh', $employee->name);
        $this->assertNotNull($first->fresh()->dipakai_pada);
        $this->assertNotNull($second->fresh()->dipakai_pada);
        $this->assertDatabaseHas('activities', ['user_id' => $employee->id, 'module' => 'dokumentasi', 'action' => 'menambahkan', 'target' => 'Samsat Keliling di Kelurahan Oebobo.']);
    }

    public function test_stores_empty_or_missing_optional_fields_as_empty(): void
    {
        $empty = ['deskripsi' => null, 'pemeriksa' => null] + $this->activity();
        $missing = array_diff_key($empty, array_flip(['deskripsi', 'pemeriksa']));

        foreach ([$empty, $missing] as $activity) {
            $this->actingAs($this->dokumentasiEmployee())->postJson('/api/koleksi/dokumentasi', $activity)
                ->assertCreated()
                ->assertJsonPath('deskripsi', '')
                ->assertJsonPath('pemeriksa', '')
                ->assertJsonPath('foto', []);
        }
    }

    public function test_validates_a_new_activity(): void
    {
        $activity = $this->activity();
        $activity['judul'] = '';
        $activity['kategori'] = 'rapat';
        $activity['lokasi'] = str_repeat('a', 121);
        $activity['tanggal'] = '13/10/2026';
        $activity['deskripsi'] = "Deskripsi\u{0007}";
        $activity['penyedia'] = '';
        unset($activity['foto']);

        $response = $this->actingAs($this->dokumentasiEmployee())->postJson('/api/koleksi/dokumentasi', $activity);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'judul' => 'Nama kegiatan wajib diisi.',
            'kategori' => 'Kategori kegiatan tidak valid.',
            'lokasi' => 'Lokasi maksimal 120 karakter.',
            'tanggal' => 'Pilih tanggal kegiatan.',
            'deskripsi' => 'Deskripsi berisi karakter kontrol yang tidak diizinkan.',
            'foto' => 'Daftar foto wajib dikirim.',
            'penyedia' => 'Unit penyedia wajib diisi.',
        ]);
        $this->assertDatabaseCount('documentations', 9);
    }

    public function test_limits_the_texts_and_photos(): void
    {
        $photos = array_map(fn (int $index): array => ['id' => "f{$index}", 'src' => $this->photo()->url(), 'keterangan' => ''], range(1, 31));
        $activity = [
            'judul' => str_repeat('a', 161),
            'deskripsi' => str_repeat('a', 601),
            'foto' => $photos,
        ] + $this->activity();

        $this->actingAs($this->dokumentasiEmployee())->postJson('/api/koleksi/dokumentasi', $activity)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'judul' => 'Nama kegiatan maksimal 160 karakter.',
                'deskripsi' => 'Deskripsi maksimal 600 karakter.',
                'foto' => 'Maksimal 30 foto per kegiatan.',
            ]);
    }

    public function test_validates_the_photos(): void
    {
        $photo = $this->photo();
        $cover = Upload::factory()->create(['tujuan' => UploadTarget::Berita]);
        $activity = ['foto' => [
            ['id' => 'a', 'src' => $photo->url(), 'keterangan' => str_repeat('a', 201)],
            ['id' => 'a', 'src' => $photo->url(), 'keterangan' => "Foto\u{0007}"],
            ['id' => 'c', 'src' => $cover->url(), 'keterangan' => ''],
            ['id' => 'd', 'src' => 'https://example.com/foto.jpg', 'keterangan' => ''],
            ['id' => 'e', 'keterangan' => ''],
        ]] + $this->activity();

        $this->actingAs($this->dokumentasiEmployee())->postJson('/api/koleksi/dokumentasi', $activity)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'foto.0.keterangan' => 'Keterangan foto maksimal 200 karakter.',
                'foto.1.id' => 'Foto tercantum dua kali.',
                'foto.1.src' => 'Foto tercantum dua kali.',
                'foto.1.keterangan' => 'Keterangan foto berisi karakter kontrol yang tidak diizinkan.',
                'foto.2.src' => 'Foto harus diunggah lewat dashboard.',
                'foto.3.src' => 'Foto harus diunggah lewat dashboard.',
                'foto.4.src' => 'Foto harus diunggah lewat dashboard.',
            ]);
        $this->assertNull($photo->fresh()->dipakai_pada);
    }

    public function test_rejects_a_photo_another_activity_uses(): void
    {
        $photo = $this->photo();
        $other = Documentation::factory()->withPhotos([$photo->url()])->create();
        $photo->markUsed();
        $activity = Documentation::factory()->create();

        $this->actingAs($this->dokumentasiEmployee())->patchJson("/api/koleksi/dokumentasi/{$activity->id}", ['foto' => [['id' => 'a', 'src' => $photo->url(), 'keterangan' => '']]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['foto.0.src' => 'Foto ini sudah dipakai kegiatan lain. Unggah ulang fotonya.']);
        $this->actingAs($this->dokumentasiEmployee())->patchJson("/api/koleksi/dokumentasi/{$other->id}", ['foto' => [['id' => 'a', 'src' => $photo->url(), 'keterangan' => 'Baru.']]])
            ->assertOk()
            ->assertJsonPath('foto.0.keterangan', 'Baru.');
        $this->actingAs($this->dokumentasiEmployee())->patchJson("/api/koleksi/dokumentasi/{$other->id}", ['foto' => [['id' => 'a', 'keterangan' => 'Tanpa foto.']]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['foto.0.src' => 'Foto harus diunggah lewat dashboard.']);
    }

    public function test_keeps_the_photos_in_use_and_deletes_the_ones_removed(): void
    {
        Storage::fake('public');
        $employee = $this->dokumentasiEmployee();
        [$first, $second, $third] = [$this->photo(), $this->photo(), $this->photo()];
        foreach ([$first, $second, $third] as $photo) {
            Storage::disk('public')->put($photo->path, 'foto');
        }
        $photos = fn (Upload ...$uploads): array => array_map(fn (Upload $upload): array => ['id' => $upload->id, 'src' => $upload->url(), 'keterangan' => ''], $uploads);
        $id = $this->actingAs($employee)->postJson('/api/koleksi/dokumentasi', ['foto' => $photos($first, $second)] + $this->activity())->json('id');

        $this->actingAs($employee)->patchJson("/api/koleksi/dokumentasi/{$id}", ['foto' => $photos($second, $first, $third)])->assertOk()->assertJsonPath('foto.0.src', $second->url());
        $this->assertModelExists($first);
        $this->assertNotNull($third->fresh()->dipakai_pada);

        $this->actingAs($employee)->patchJson("/api/koleksi/dokumentasi/{$id}", ['foto' => $photos($third)])->assertOk();
        $this->assertModelMissing($first);
        $this->assertModelMissing($second);
        Storage::disk('public')->assertMissing([$first->path, $second->path]);
        Storage::disk('public')->assertExists($third->path);
    }

    public function test_changes_an_activity_and_logs_only_real_changes(): void
    {
        $employee = $this->dokumentasiEmployee();
        $activity = Documentation::factory()->create();

        $this->actingAs($employee)->patchJson("/api/koleksi/dokumentasi/{$activity->id}", ['lokasi' => 'Kec. Oebobo', 'tanggal' => '2026-10-14'])
            ->assertOk()
            ->assertJsonPath('lokasi', 'Kec. Oebobo');
        $this->actingAs($employee)->patchJson("/api/koleksi/dokumentasi/{$activity->id}", ['lokasi' => 'Kec. Oebobo', 'tanggal' => '2026-10-14'])->assertOk();

        $this->assertDatabaseCount('activities', 1);
        $this->assertDatabaseHas('activities', ['module' => 'dokumentasi', 'action' => 'memperbarui', 'target' => $activity->judul]);
    }

    #[TestWith([WorkflowStatus::AwaitingApproval])]
    #[TestWith([WorkflowStatus::Published])]
    public function test_lets_module_managers_change_activities_awaiting_approval_or_published(WorkflowStatus $status): void
    {
        $activity = Documentation::factory()->withPhotos([$this->photo()->url()])->withStatus($status)->create();

        $this->actingAs($this->dokumentasiEmployee())->patchJson("/api/koleksi/dokumentasi/{$activity->id}", ['lokasi' => 'Kec. Oebobo'])
            ->assertOk()
            ->assertJsonPath('lokasi', 'Kec. Oebobo')
            ->assertJsonPath('status', $status->value);
    }

    public function test_lets_only_administrators_delete_activities_with_their_photos(): void
    {
        Storage::fake('public');
        [$first, $second] = [$this->photo(), $this->photo()];
        Storage::disk('public')->put($first->path, 'foto');
        Storage::disk('public')->put($second->path, 'foto');
        $withPhotos = Documentation::factory()->withPhotos([$first->url(), $second->url()])->create();
        $withoutPhotos = Documentation::factory()->create();

        $this->actingAs($this->dokumentasiEmployee())->postJson('/api/koleksi/dokumentasi/hapus', ['ids' => [$withPhotos->id]])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Hanya Administrator yang dapat menghapus dokumentasi.']);
        $this->assertModelExists($first);

        $this->actingAs(User::factory()->administrator()->create())->postJson('/api/koleksi/dokumentasi/hapus', ['ids' => [$withPhotos->id, $withoutPhotos->id]])->assertNoContent();

        $this->assertModelMissing($withPhotos);
        $this->assertModelMissing($withoutPhotos);
        $this->assertModelMissing($first);
        $this->assertModelMissing($second);
        Storage::disk('public')->assertMissing([$first->path, $second->path]);
        $this->assertDatabaseHas('activities', ['module' => 'dokumentasi', 'action' => 'menghapus', 'target' => '2 entri']);
    }

    private function dokumentasiEmployee(): User
    {
        return User::factory()->withModules(DashboardModule::Dokumentasi)->create();
    }

    private function photo(): Upload
    {
        return Upload::factory()->create(['tujuan' => UploadTarget::Dokumentasi]);
    }

    /**
     * @return array<string, mixed>
     */
    private function activity(): array
    {
        return [
            'judul' => 'Samsat Keliling di Kelurahan Oebobo.',
            'kategori' => 'keliling',
            'lokasi' => 'Kec. Oebobo',
            'tanggal' => '2026-10-13',
            'deskripsi' => 'Pelayanan pajak tahunan di Kantor Kelurahan Oebobo.',
            'foto' => [],
            'penyedia' => 'Seksi Penetapan dan Penagihan',
            'pemeriksa' => 'Kepala Seksi Penetapan dan Penagihan',
        ];
    }
}
