<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\FieldActivity;
use App\Models\FieldAttendance;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class FieldActivityControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_401_to_guests(): void
    {
        $this->getJson('/api/absensi/kegiatan')->assertUnauthorized();
        $this->postJson('/api/absensi/kegiatan', $this->activity())->assertUnauthorized();
    }

    #[TestWith(['GET', '/api/absensi/kegiatan'])]
    #[TestWith(['POST', '/api/absensi/kegiatan'])]
    #[TestWith(['POST', '/api/absensi/kegiatan/hapus'])]
    #[TestWith(['GET', '/api/absensi/kegiatan/{id}'])]
    #[TestWith(['PATCH', '/api/absensi/kegiatan/{id}'])]
    public function test_forbids_employees_who_are_not_administrators_with_403(string $method, string $uri): void
    {
        $activity = FieldActivity::factory()->create();
        $employee = User::factory()->withModules(...DashboardModule::cases())->create();
        $activity->officers()->attach($employee);

        $response = $this->actingAs($employee)->json($method, str_replace('{id}', $activity->id, $uri), ['ids' => [$activity->id]] + $this->activity());

        $response->assertForbidden()->assertExactJson(['message' => 'Hanya Administrator yang dapat mengelola kegiatan lapangan.']);
        $this->assertModelExists($activity);
    }

    public function test_lists_activities_newest_date_first_with_their_officers_and_clock_in_count(): void
    {
        // Created out of name order, so the order of their keys differs from the order of their names
        [$citra, $ani, $budi] = $this->officers('Citra', 'Ani', 'Budi');
        $sameDay = FieldActivity::factory()->create(['nama' => 'Tilang gabungan Bundaran PU', 'tanggal' => '2026-10-20']);
        $earlier = FieldActivity::factory()->create(['nama' => 'Tilang gabungan Oesapa', 'tanggal' => '2026-10-01']);
        $later = FieldActivity::factory()->create(['nama' => 'Tilang gabungan El Tari', 'tanggal' => '2026-10-20', 'catatan' => 'Bawa rompi.']);
        $later->officers()->attach([$budi->id, $ani->id, $citra->id]);
        FieldAttendance::factory()->for($later, 'activity')->for($budi)->create();

        $response = $this->actingAs($this->administrator())->getJson('/api/absensi/kegiatan');

        $response->assertOk()
            ->assertJsonPath('*.id', [$sameDay->id, $later->id, $earlier->id])
            ->assertJsonPath('1.nama', 'Tilang gabungan El Tari')
            ->assertJsonPath('1.tanggal', '2026-10-20')
            ->assertJsonPath('1.tempat', 'Jl. El Tari')
            ->assertJsonPath('1.lat', -10.1637)
            ->assertJsonPath('1.lng', 123.5974)
            ->assertJsonPath('1.radius', 150)
            ->assertJsonPath('1.catatan', 'Bawa rompi.')
            ->assertJsonPath('1.petugas', [
                ['id' => $ani->id, 'nama' => 'Ani', 'nip' => $ani->nip],
                ['id' => $budi->id, 'nama' => 'Budi', 'nip' => $budi->nip],
                ['id' => $citra->id, 'nama' => 'Citra', 'nip' => $citra->nip],
            ])
            ->assertJsonPath('1.jumlahHadir', 1)
            ->assertJsonPath('2.petugas', [])
            ->assertJsonPath('2.jumlahHadir', 0)
            ->assertJsonMissingPath('1.absen');
    }

    public function test_adds_an_activity_with_its_officers(): void
    {
        [$ani, $budi] = $this->officers('Ani', 'Budi');

        $response = $this->actingAs($this->administrator())->postJson('/api/absensi/kegiatan', [
            'lat' => -10.16370049,
            'lng' => 123.59740051,
            'catatan' => null,
            'petugas' => [$budi->id, $ani->id],
        ] + $this->activity());

        $activity = FieldActivity::query()->sole();
        $response->assertCreated()
            ->assertJsonPath('id', $activity->id)
            ->assertJsonPath('nama', 'Tilang gabungan Jl. El Tari')
            ->assertJsonPath('tanggal', '2026-10-20')
            ->assertJsonPath('lat', -10.1637)
            ->assertJsonPath('lng', 123.597401)
            ->assertJsonPath('radius', 120)
            ->assertJsonPath('catatan', '')
            ->assertJsonPath('petugas.*.id', [$ani->id, $budi->id])
            ->assertJsonPath('jumlahHadir', 0);
        $this->assertEqualsCanonicalizing([$ani->id, $budi->id], $activity->officers->modelKeys());
    }

    public function test_validates_a_new_activity(): void
    {
        $administrator = $this->administrator();

        $this->actingAs($administrator)->postJson('/api/absensi/kegiatan', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'nama' => 'Nama kegiatan wajib diisi.',
                'tanggal' => 'Pilih tanggal kegiatan.',
                'tempat' => 'Tempat wajib diisi.',
                'lat' => 'Tentukan titik lokasi kegiatan.',
                'lng' => 'Tentukan titik lokasi kegiatan.',
                'radius' => 'Isi radius absen.',
                'petugas' => 'Daftar petugas wajib dikirim.',
            ]);

        $this->actingAs($administrator)->postJson('/api/absensi/kegiatan', [
            'nama' => str_repeat('a', 161),
            'tanggal' => '20-10-2026',
            'tempat' => "Jl. El Tari\u{0007}",
            'lat' => -90.5,
            'lng' => 180.5,
            'radius' => 19,
            'catatan' => str_repeat('a', 301),
            'petugas' => [User::factory()->create()->id, 'tidak-ada'],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'nama' => 'Nama kegiatan maksimal 160 karakter.',
                'tanggal' => 'Pilih tanggal kegiatan.',
                'tempat' => 'Tempat berisi karakter kontrol yang tidak diizinkan.',
                'lat' => 'Lintang harus di antara -90 dan 90.',
                'lng' => 'Bujur harus di antara -180 dan 180.',
                'radius' => 'Radius 20–2000 meter.',
                'catatan' => 'Catatan maksimal 300 karakter.',
                'petugas.1' => 'Petugas tidak ditemukan.',
            ])
            ->assertJsonMissingValidationErrors('petugas.0');

        $officer = User::factory()->create();
        $this->actingAs($administrator)->postJson('/api/absensi/kegiatan', ['radius' => 2001, 'petugas' => [$officer->id, $officer->id]] + $this->activity())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['radius' => 'Radius 20–2000 meter.', 'petugas.0' => 'Petugas tercantum dua kali.']);
        $this->actingAs($administrator)->postJson('/api/absensi/kegiatan', ['radius' => 50.5, 'petugas' => 'semua'] + $this->activity())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['radius' => 'Radius dalam meter bulat.', 'petugas' => 'Daftar petugas tidak valid.']);
        $this->actingAs($administrator)->postJson('/api/absensi/kegiatan', ['petugas' => array_map(fn (int $i): string => "petugas-{$i}", range(1, 201))] + $this->activity())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['petugas' => 'Maksimal 200 petugas per kegiatan.']);
        $this->actingAs($administrator)->postJson('/api/absensi/kegiatan', ['radius' => 20] + $this->activity())->assertCreated();
        $this->actingAs($administrator)->postJson('/api/absensi/kegiatan', ['radius' => 2000] + $this->activity())->assertCreated();
    }

    public function test_changes_the_details_or_the_officers_and_keeps_recorded_clock_ins(): void
    {
        [$ani, $budi] = $this->officers('Ani', 'Budi');
        $activity = FieldActivity::factory()->create(['catatan' => 'Bawa rompi.']);
        $activity->officers()->attach($ani);
        $attendance = FieldAttendance::factory()->for($activity, 'activity')->for($ani)->create(['jarak' => 40]);

        $this->actingAs($this->administrator())->patchJson("/api/absensi/kegiatan/{$activity->id}", ['radius' => 300, 'lat' => -10.1601234567])
            ->assertOk()
            ->assertJsonPath('radius', 300)
            ->assertJsonPath('lat', -10.160123)
            ->assertJsonPath('catatan', 'Bawa rompi.')
            ->assertJsonPath('petugas.*.id', [$ani->id])
            ->assertJsonPath('jumlahHadir', 1);

        $this->actingAs($this->administrator())->patchJson("/api/absensi/kegiatan/{$activity->id}", ['petugas' => [$budi->id], 'catatan' => null])
            ->assertOk()
            ->assertJsonPath('radius', 300)
            ->assertJsonPath('catatan', '')
            ->assertJsonPath('petugas.*.id', [$budi->id]);

        $this->assertSame([$budi->id], $activity->officers()->pluck('users.id')->all());
        $this->assertSame(40, $attendance->fresh()->jarak);
    }

    public function test_validates_changes_to_an_activity(): void
    {
        $activity = FieldActivity::factory()->create();

        $this->actingAs($this->administrator())->patchJson("/api/absensi/kegiatan/{$activity->id}", ['nama' => '', 'radius' => 0, 'petugas' => null])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nama' => 'Nama kegiatan wajib diisi.', 'radius' => 'Radius 20–2000 meter.', 'petugas' => 'Daftar petugas tidak valid.']);
        $this->actingAs($this->administrator())->patchJson('/api/absensi/kegiatan/01k7aaaaaaaaaaaaaaaaaaaaaa', ['radius' => 100])
            ->assertNotFound();
    }

    public function test_shows_the_clock_ins_of_an_activity(): void
    {
        [$ani, $budi] = $this->officers('Ani', 'Budi');
        $activity = FieldActivity::factory()->create();
        $activity->officers()->attach($ani);
        $attendance = FieldAttendance::factory()->for($activity, 'activity')->for($budi)->create([
            'waktu' => '2026-10-20 00:12:34',
            'lat' => -10.163512,
            'lng' => 123.597611,
            'akurasi' => 9,
            'jarak' => 31,
            'foto' => 'http://localhost/api/berkas/01k7bbbbbbbbbbbbbbbbbbbbbb',
        ]);

        $response = $this->actingAs($this->administrator())->getJson("/api/absensi/kegiatan/{$activity->id}");

        $response->assertOk()
            ->assertJsonPath('petugas.*.id', [$ani->id])
            ->assertJsonPath('jumlahHadir', 1)
            ->assertJsonPath('absen', [[
                'id' => $attendance->id,
                'petugas' => ['id' => $budi->id, 'nama' => 'Budi', 'nip' => $budi->nip],
                'waktu' => '2026-10-20T00:12:34+00:00',
                'lat' => -10.163512,
                'lng' => 123.597611,
                'akurasi' => 9,
                'jarak' => 31,
                'foto' => 'http://localhost/api/berkas/01k7bbbbbbbbbbbbbbbbbbbbbb',
            ]]);
    }

    public function test_deletes_activities_with_their_clock_ins_and_selfies(): void
    {
        Storage::fake();
        [$ani] = $this->officers('Ani');
        $activity = FieldActivity::factory()->create();
        $other = FieldActivity::factory()->create();
        $selfie = Upload::factory()->attendance()->for($ani)->create(['dipakai_pada' => now()]);
        $kept = Upload::factory()->attendance()->for($ani)->create(['dipakai_pada' => now()]);
        Storage::put($selfie->path, 'jpeg');
        Storage::put($kept->path, 'jpeg');
        $attendance = FieldAttendance::factory()->for($activity, 'activity')->for($ani)->create(['foto' => $selfie->url()]);
        $otherAttendance = FieldAttendance::factory()->for($other, 'activity')->for($ani)->create(['foto' => $kept->url()]);
        $activity->officers()->attach($ani);

        $this->actingAs($this->administrator())->postJson('/api/absensi/kegiatan/hapus', ['ids' => [$activity->id, '01k7aaaaaaaaaaaaaaaaaaaaaa']])
            ->assertNoContent();

        $this->assertModelMissing($activity);
        $this->assertModelMissing($attendance);
        $this->assertModelMissing($selfie);
        Storage::assertMissing($selfie->path);
        $this->assertDatabaseMissing('field_activity_user', ['field_activity_id' => $activity->id]);
        $this->assertModelExists($other);
        $this->assertModelExists($otherAttendance);
        $this->assertModelExists($kept);
        Storage::assertExists($kept->path);
    }

    public function test_validates_the_activities_to_delete(): void
    {
        $this->actingAs($this->administrator())->postJson('/api/absensi/kegiatan/hapus', ['ids' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ids' => 'Pilih kegiatan yang akan dihapus.']);
    }

    private function administrator(): User
    {
        return User::factory()->administrator()->create();
    }

    /**
     * @return list<User>
     */
    private function officers(string ...$names): array
    {
        return array_map(fn (string $name): User => User::factory()->create(['name' => $name]), $names);
    }

    /**
     * @return array<string, mixed>
     */
    private function activity(): array
    {
        return [
            'nama' => 'Tilang gabungan Jl. El Tari',
            'tanggal' => '2026-10-20',
            'tempat' => 'Depan Kantor Gubernur',
            'lat' => -10.1637,
            'lng' => 123.5974,
            'radius' => 120,
            'catatan' => '',
            'petugas' => [],
        ];
    }
}
