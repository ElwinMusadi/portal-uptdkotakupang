<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Enums\UploadTarget;
use App\Models\OrganizationUnit;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class OrganizationUnitControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_401_to_guests(): void
    {
        $this->getJson('/api/koleksi/unit')->assertUnauthorized();
        $this->postJson('/api/koleksi/unit', $this->unit())->assertUnauthorized();
    }

    #[TestWith(['GET', '/api/koleksi/unit'])]
    #[TestWith(['POST', '/api/koleksi/unit'])]
    #[TestWith(['PUT', '/api/koleksi/unit/urutan'])]
    #[TestWith(['POST', '/api/koleksi/unit/hapus'])]
    public function test_forbids_employees_without_the_module_with_403(string $method, string $uri): void
    {
        $employee = User::factory()->withModules(DashboardModule::Pemutakhiran)->create();

        $response = $this->actingAs($employee)->json($method, $uri, $this->unit());

        $response->assertForbidden()->assertExactJson(['message' => 'Modul Struktur Organisasi belum ditugaskan kepada Anda.']);
    }

    public function test_lists_the_structure_the_portal_showed_in_order(): void
    {
        $response = $this->actingAs($this->unitEmployee())->getJson('/api/koleksi/unit');
        $head = $response->json('0.id');

        $response->assertOk()
            ->assertJsonPath('*.nama', ['Kepala UPTD', 'Subbagian Tata Usaha', 'Seksi Penetapan dan Penagihan', 'Seksi Verifikasi', 'Kelompok Jabatan Fungsional', 'Polri · Satlantas', 'PT Jasa Raharja'])
            ->assertJsonPath('*.singkatan', ['KU', 'TU', 'S1', 'S2', 'JF', 'PL', 'JR'])
            ->assertJsonPath('*.induk', [null, $head, $head, $head, $head, null, null])
            ->assertJsonPath('0.pejabat', 'Remmy Christian Pah, S.STP, M.Si')
            ->assertJsonPath('1.pejabat', '')
            ->assertJsonPath('1.anggota', ['Pengelola kepegawaian', 'Pengelola keuangan', 'Pengelola barang & perlengkapan', 'Penata kelola sistem & teknologi informasi'])
            ->assertJsonPath('1.catatan', 'Pengelolaan portal PINTU berada di bawah unit ini.')
            ->assertJsonPath('4.catatan', 'Sesuai formasi yang ditetapkan')
            ->assertJsonPath('5.jenis', 'mitra');
        $this->assertSame(strtolower($head), $head);
    }

    public function test_adds_a_unit_at_the_end_and_logs_it(): void
    {
        $employee = $this->unitEmployee();
        $head = OrganizationUnit::query()->where('jenis', 'pimpinan')->firstOrFail();
        $photo = $this->photo();

        $response = $this->actingAs($employee)->postJson('/api/koleksi/unit', [
            'induk' => $head->id,
            'singkatan' => 's3',
            'nip' => '1985 0101 2010 0110 01',
            'foto' => $photo->url(),
            'anggota' => ['Petugas pengolahan data', null],
        ] + $this->unit());

        $response->assertCreated()
            ->assertJsonPath('nama', 'Seksi Pengolahan Data')
            ->assertJsonPath('singkatan', 'S3')
            ->assertJsonPath('induk', $head->id)
            ->assertJsonPath('nip', '198501012010011001')
            ->assertJsonPath('foto', $photo->url())
            ->assertJsonPath('anggota', ['Petugas pengolahan data'])
            ->assertJsonPath('urutan', 7);
        $this->assertNotNull($photo->fresh()->dipakai_pada);
        $this->assertDatabaseHas('activities', ['user_id' => $employee->id, 'module' => 'unit', 'action' => 'menambahkan', 'target' => 'Seksi Pengolahan Data']);
    }

    public function test_stores_empty_or_missing_optional_fields_as_empty(): void
    {
        $empty = ['induk' => null, 'jabatan' => null, 'pejabat' => null, 'nip' => null, 'foto' => null, 'uraianTugas' => null, 'catatan' => null] + $this->unit();
        $missing = array_diff_key($empty, array_flip(['induk', 'jabatan', 'pejabat', 'nip', 'foto', 'uraianTugas', 'catatan']));

        foreach ([$empty, $missing] as $unit) {
            $this->actingAs($this->unitEmployee())->postJson('/api/koleksi/unit', $unit)
                ->assertCreated()
                ->assertJsonPath('induk', null)
                ->assertJsonPath('jabatan', '')
                ->assertJsonPath('pejabat', '')
                ->assertJsonPath('nip', '')
                ->assertJsonPath('foto', null)
                ->assertJsonPath('uraianTugas', '')
                ->assertJsonPath('catatan', '');
        }
    }

    public function test_validates_a_new_unit(): void
    {
        $partner = OrganizationUnit::query()->where('jenis', 'mitra')->firstOrFail();
        $unit = [
            'nama' => '',
            'singkatan' => 'SEK1',
            'jenis' => 'bidang',
            'induk' => $partner->id,
            'jabatan' => str_repeat('a', 121),
            'pejabat' => "Nama\u{0007}",
            'nip' => '12345',
            'anggota' => array_fill(0, 21, 'Petugas'),
            'uraianTugas' => str_repeat('a', 1001),
            'catatan' => str_repeat('a', 201),
        ] + $this->unit();

        $this->actingAs($this->unitEmployee())->postJson('/api/koleksi/unit', $unit)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'nama' => 'Nama unit wajib diisi.',
                'singkatan' => 'Singkatan 1–3 huruf atau angka.',
                'jenis' => 'Jenis unit tidak valid.',
                'induk' => 'Pilih unit di atasnya dari daftar.',
                'jabatan' => 'Jabatan maksimal 120 karakter.',
                'pejabat' => 'Nama pejabat berisi karakter kontrol yang tidak diizinkan.',
                'nip' => 'NIP 18 digit angka.',
                'anggota' => 'Maksimal 20 jabatan pelaksana.',
                'uraianTugas' => 'Uraian tugas maksimal 1000 karakter.',
                'catatan' => 'Catatan maksimal 200 karakter.',
            ]);
        $this->actingAs($this->unitEmployee())->postJson('/api/koleksi/unit', ['induk' => '01m4zzzzzzzzzzzzzzzzzzzzzz', 'nama' => str_repeat('a', 121), 'anggota' => [str_repeat('a', 121)]] + $this->unit())
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'induk' => 'Pilih unit di atasnya dari daftar.',
                'nama' => 'Nama unit maksimal 120 karakter.',
                'anggota.0' => 'Jabatan pelaksana maksimal 120 karakter.',
            ]);
        $this->assertDatabaseCount('organization_units', 7);
    }

    public function test_keeps_units_off_their_own_branch(): void
    {
        $head = OrganizationUnit::query()->where('jenis', 'pimpinan')->firstOrFail();
        $section = OrganizationUnit::query()->where('singkatan', 'S1')->firstOrFail();
        $team = OrganizationUnit::factory()->under($section)->create();

        foreach ([$head->id => $head, $section->id => $head, $team->id => $section] as $parent => $unit) {
            $this->actingAs($this->unitEmployee())->patchJson("/api/koleksi/unit/{$unit->id}", ['induk' => $parent])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['induk' => 'Unit tidak bisa berada di bawah dirinya sendiri atau unit yang dibawahinya.']);
        }
        $this->actingAs($this->unitEmployee())->patchJson("/api/koleksi/unit/{$team->id}", ['induk' => $head->id])->assertOk()->assertJsonPath('induk', $head->id);
        $this->assertSame($head->id, $section->fresh()->induk);
    }

    public function test_changes_a_unit_logs_only_real_changes_and_replaces_its_photo(): void
    {
        Storage::fake('public');
        $employee = $this->unitEmployee();
        [$first, $second] = [$this->photo(), $this->photo()];
        Storage::disk('public')->put($first->path, 'foto');
        $unit = OrganizationUnit::query()->where('singkatan', 'S2')->firstOrFail();

        $this->actingAs($employee)->patchJson("/api/koleksi/unit/{$unit->id}", ['pejabat' => 'Maria Bria, S.E.', 'foto' => $first->url()])
            ->assertOk()
            ->assertJsonPath('pejabat', 'Maria Bria, S.E.');
        $this->actingAs($employee)->patchJson("/api/koleksi/unit/{$unit->id}", ['pejabat' => 'Maria Bria, S.E.', 'foto' => $first->url()])->assertOk();
        $this->assertDatabaseCount('activities', 1);
        $this->assertDatabaseHas('activities', ['module' => 'unit', 'action' => 'memperbarui', 'target' => 'Seksi Verifikasi']);

        $this->actingAs($employee)->patchJson("/api/koleksi/unit/{$unit->id}", ['foto' => $second->url()])->assertOk();
        $this->assertModelMissing($first);
        Storage::disk('public')->assertMissing($first->path);
        $this->assertNotNull($second->fresh()->dipakai_pada);
    }

    public function test_rejects_photos_that_are_not_its_uploads_or_belong_to_another_unit(): void
    {
        $news = Upload::factory()->create(['tujuan' => UploadTarget::Berita]);
        $this->actingAs($this->unitEmployee())->postJson('/api/koleksi/unit', ['foto' => $news->url()] + $this->unit())
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['foto' => 'Foto pejabat harus diunggah lewat dashboard.']);

        $photo = $this->photo();
        $owner = OrganizationUnit::factory()->create(['foto' => $photo->url()]);
        $photo->markUsed();
        $unit = OrganizationUnit::factory()->create();
        $this->actingAs($this->unitEmployee())->patchJson("/api/koleksi/unit/{$unit->id}", ['foto' => $photo->url()])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['foto' => 'Foto ini sudah dipakai unit lain. Unggah ulang fotonya.']);
        $this->actingAs($this->unitEmployee())->patchJson("/api/koleksi/unit/{$owner->id}", ['foto' => $photo->url(), 'pejabat' => 'Yohanes'])
            ->assertOk()
            ->assertJsonPath('pejabat', 'Yohanes');
    }

    public function test_reorders_units_without_touching_their_update_time(): void
    {
        $ids = OrganizationUnit::query()->orderBy('urutan')->pluck('id')->all();
        $before = OrganizationUnit::query()->whereKey($ids[3])->value('updated_at');
        $this->travel(1)->hours();

        $this->actingAs($this->unitEmployee())->putJson('/api/koleksi/unit/urutan', ['ids' => [$ids[3], $ids[1]]])->assertNoContent();

        $this->actingAs($this->unitEmployee())->getJson('/api/koleksi/unit')
            ->assertOk()
            ->assertJsonPath('*.id', [$ids[3], $ids[1], $ids[0], $ids[2], $ids[4], $ids[5], $ids[6]]);
        $this->assertEquals($before, OrganizationUnit::query()->whereKey($ids[3])->value('updated_at'));
        $this->assertDatabaseHas('activities', ['module' => 'unit', 'action' => 'mengubah urutan', 'target' => '2 unit']);
        $this->actingAs($this->unitEmployee())->putJson('/api/koleksi/unit/urutan', ['ids' => [$ids[0], '01m4zzzzzzzzzzzzzzzzzzzzzz']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ids' => 'Urutan tidak cocok dengan data terbaru. Muat ulang halaman lalu coba lagi.']);
    }

    public function test_deletes_units_with_their_photos_but_not_units_others_report_to(): void
    {
        Storage::fake('public');
        $employee = $this->unitEmployee();
        $section = OrganizationUnit::query()->where('singkatan', 'S1')->firstOrFail();
        $photo = $this->photo();
        Storage::disk('public')->put($photo->path, 'foto');
        $team = OrganizationUnit::factory()->under($section)->create(['foto' => $photo->url()]);

        $this->actingAs($employee)->postJson('/api/koleksi/unit/hapus', ['ids' => [$section->id]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ids' => 'Unit masih membawahi unit lain. Pindahkan unit di bawahnya terlebih dahulu.']);
        $this->assertModelExists($section);

        $this->actingAs($employee)->postJson('/api/koleksi/unit/hapus', ['ids' => [$section->id, $team->id]])->assertNoContent();

        $this->assertModelMissing($section);
        $this->assertModelMissing($team);
        $this->assertModelMissing($photo);
        Storage::disk('public')->assertMissing($photo->path);
        $this->assertDatabaseHas('activities', ['module' => 'unit', 'action' => 'menghapus', 'target' => '2 entri']);
    }

    private function unitEmployee(): User
    {
        return User::factory()->withModules(DashboardModule::Unit)->create();
    }

    private function photo(): Upload
    {
        return Upload::factory()->create(['tujuan' => UploadTarget::Unit]);
    }

    /**
     * @return array<string, mixed>
     */
    private function unit(): array
    {
        return [
            'nama' => 'Seksi Pengolahan Data',
            'singkatan' => 'S3',
            'jenis' => 'seksi',
            'induk' => null,
            'jabatan' => 'Kepala Seksi',
            'pejabat' => '',
            'nip' => '',
            'foto' => null,
            'anggota' => [],
            'uraianTugas' => 'Mengolah data pajak kendaraan.',
            'catatan' => '',
        ];
    }
}
