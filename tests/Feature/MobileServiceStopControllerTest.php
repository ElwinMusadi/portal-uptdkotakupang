<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\MobileServiceStop;
use App\Models\ServiceLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class MobileServiceStopControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const string MODULE_ONLY = 'Modul Layanan Luar Kantor belum ditugaskan kepada Anda.';

    protected function setUp(): void
    {
        parent::setUp();

        // Wednesday 14 October 2026 in Kupang
        $this->travelTo(Carbon::parse('2026-10-14 10:00:00', 'Asia/Makassar'));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function routes(): array
    {
        return [
            'list' => ['GET', '/api/koleksi/keliling'],
            'add' => ['POST', '/api/koleksi/keliling'],
            'read' => ['GET', '/api/koleksi/keliling/{id}'],
            'edit' => ['PATCH', '/api/koleksi/keliling/{id}'],
        ];
    }

    #[DataProvider('routes')]
    public function test_returns_401_to_guests(string $method, string $uri): void
    {
        $stop = MobileServiceStop::factory()->create();

        $response = $this->json($method, str_replace('{id}', $stop->id, $uri), $this->newStop());

        $response->assertUnauthorized();
    }

    #[DataProvider('routes')]
    public function test_forbids_employees_without_the_module_with_403(string $method, string $uri): void
    {
        $stop = MobileServiceStop::factory()->create();
        $employee = User::factory()->withModules(DashboardModule::Faq)->create();

        $response = $this->actingAs($employee)->json($method, str_replace('{id}', $stop->id, $uri), $this->newStop());

        $response->assertForbidden()->assertExactJson(['message' => self::MODULE_ONLY]);
        $this->assertDatabaseCount('mobile_service_stops', 1);
    }

    public function test_lets_update_schedule_managers_list_the_entries_but_not_change_them(): void
    {
        MobileServiceStop::factory()->create();
        $monitor = User::factory()->withModules(DashboardModule::Pemutakhiran)->create();

        $this->actingAs($monitor)->getJson('/api/koleksi/keliling')->assertOk()->assertJsonCount(1);
        $this->actingAs($monitor)->postJson('/api/koleksi/keliling', $this->newStop())->assertForbidden();
    }

    public function test_lists_every_entry_by_kind_date_and_time(): void
    {
        MobileServiceStop::factory()->on('2026-10-17')->create(['mulai' => '17:00', 'selesai' => '19:00', 'lokasi' => 'Tamnos']);
        MobileServiceStop::factory()->counter('2026-10-19', '2026-10-23')->create(['lokasi' => 'Kantor Lurah Oebobo']);
        MobileServiceStop::factory()->outlet('Mall Pelayanan Publik', [1, 2, 3, 4, 5])->inactive()->create(['lokasi' => 'Gedung MPP']);
        $first = MobileServiceStop::factory()->on('2026-10-17', '02')->create([
            'mulai' => '06:00',
            'selesai' => '09:00',
            'lokasi' => 'CFD Eltari',
            'kecamatan' => 'Kec. Oebobo',
            'layanan' => 'Pajak tahunan',
            'catatan' => 'Bawa STNK.',
            'urutan' => 7,
        ]);

        $response = $this->actingAs($this->kelilingEmployee())->getJson('/api/koleksi/keliling');

        $response->assertOk()->assertJsonPath('1', [
            'id' => $first->id,
            'jenis' => 'keliling',
            'unit' => '02',
            'nama' => '',
            'tanggalMulai' => '2026-10-17',
            'tanggalSelesai' => '2026-10-17',
            'hari' => [6],
            'mulai' => '06:00',
            'selesai' => '09:00',
            'lokasi' => 'CFD Eltari',
            'kecamatan' => 'Kec. Oebobo',
            'layanan' => 'Pajak tahunan',
            'catatan' => 'Bawa STNK.',
            'aktif' => true,
            'urutan' => 7,
            'dibuat' => '2026-10-14T02:00:00+00:00',
            'diperbarui' => '2026-10-14T02:00:00+00:00',
        ]);
        $this->assertSame(['Gedung MPP', 'CFD Eltari', 'Tamnos', 'Kantor Lurah Oebobo'], $response->json('*.lokasi'));
    }

    public function test_shows_one_entry_and_returns_404_for_one_that_does_not_exist(): void
    {
        $stop = MobileServiceStop::factory()->create();
        $employee = $this->kelilingEmployee();

        $this->actingAs($employee)->getJson("/api/koleksi/keliling/{$stop->id}")->assertOk()->assertJsonPath('id', $stop->id);
        $this->actingAs($employee)->getJson('/api/koleksi/keliling/01kaaaaaaaaaaaaaaaaaaaaaaa')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Data tidak ditemukan.']);
    }

    public function test_adds_a_samsat_keliling_stop_on_its_date_with_201_and_logs_it(): void
    {
        MobileServiceStop::factory()->create(['urutan' => 3]);
        $employee = $this->kelilingEmployee();

        $response = $this->actingAs($employee)->postJson('/api/koleksi/keliling', [
            'id' => '01kaaaaaaaaaaaaaaaaaaaaaaa',
            'urutan' => 99,
            'nama' => 'Diabaikan',
            'tanggalSelesai' => '2026-10-20',
            'hari' => [1, 2],
        ] + $this->newStop());

        $response->assertCreated()
            ->assertJsonPath('jenis', 'keliling')
            ->assertJsonPath('unit', '01')
            ->assertJsonPath('nama', '')
            ->assertJsonPath('tanggalMulai', '2026-10-17')
            ->assertJsonPath('tanggalSelesai', '2026-10-17')
            ->assertJsonPath('hari', [6])
            ->assertJsonPath('catatan', '')
            ->assertJsonPath('urutan', 4);
        $this->assertNotSame('01kaaaaaaaaaaaaaaaaaaaaaaa', $response->json('id'));
        $this->assertDatabaseHas('activities', [
            'user_id' => $employee->id,
            'module' => 'keliling',
            'action' => 'menambahkan',
            'target' => 'Samsat Keliling 01 · Sabtu, 2026-10-17 · CFD Eltari',
        ]);
    }

    public function test_adds_a_fixed_outlet_with_its_weekdays_and_optional_dates(): void
    {
        $employee = $this->kelilingEmployee();

        $response = $this->actingAs($employee)->postJson('/api/koleksi/keliling', [
            'jenis' => 'gerai',
            'unit' => '02',
            'nama' => ' Samsat Corner ',
            'tanggalMulai' => null,
            'hari' => [5, 1, 3],
            'lokasi' => 'Lippo Plaza Kupang',
        ] + $this->newStop());

        $response->assertCreated()
            ->assertJsonPath('jenis', 'gerai')
            ->assertJsonPath('unit', null)
            ->assertJsonPath('nama', 'Samsat Corner')
            ->assertJsonPath('tanggalMulai', null)
            ->assertJsonPath('tanggalSelesai', null)
            ->assertJsonPath('hari', [1, 3, 5]);
        $this->assertDatabaseHas('activities', ['action' => 'menambahkan', 'target' => 'Samsat Corner · Lippo Plaza Kupang']);
    }

    public function test_adds_a_temporary_counter_between_two_dates(): void
    {
        $response = $this->actingAs($this->kelilingEmployee())->postJson('/api/koleksi/keliling', [
            'jenis' => 'sementara',
            'nama' => 'Loket Pameran Pembangunan',
            'tanggalMulai' => '2026-10-19',
            'tanggalSelesai' => '2026-10-25',
            'hari' => [0, 1, 2, 3, 4, 5, 6],
        ] + $this->newStop());

        $response->assertCreated()
            ->assertJsonPath('jenis', 'sementara')
            ->assertJsonPath('tanggalMulai', '2026-10-19')
            ->assertJsonPath('tanggalSelesai', '2026-10-25')
            ->assertJsonPath('hari', [0, 1, 2, 3, 4, 5, 6]);
    }

    public function test_requires_kind_times_place_district_service_and_display(): void
    {
        $response = $this->actingAs($this->kelilingEmployee())->postJson('/api/koleksi/keliling', ['catatan' => '']);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'jenis' => 'Pilih jenis layanan.',
            'mulai' => 'Isi jam mulai.',
            'selesai' => 'Isi jam selesai.',
            'lokasi' => 'Lokasi wajib diisi.',
            'kecamatan' => 'Kecamatan wajib diisi.',
            'layanan' => 'Layanan wajib diisi.',
            'aktif' => 'Tentukan apakah jadwal ini ditampilkan.',
        ]);
    }

    public function test_rejects_an_unknown_kind_and_unit(): void
    {
        $response = $this->actingAs($this->kelilingEmployee())->postJson('/api/koleksi/keliling', ['jenis' => 'pusat', 'unit' => '04'] + $this->newStop());

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'jenis' => 'Jenis layanan tidak dikenal.',
            'unit' => 'Unit Samsat Keliling tidak dikenal.',
        ]);
    }

    public function test_requires_a_unit_and_a_date_for_a_samsat_keliling_stop(): void
    {
        $response = $this->actingAs($this->kelilingEmployee())->postJson('/api/koleksi/keliling', ['unit' => null, 'tanggalMulai' => null] + $this->newStop());

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'unit' => 'Pilih unit Samsat Keliling.',
            'tanggalMulai' => 'Pilih tanggal layanan.',
        ]);
    }

    public function test_requires_a_name_and_weekdays_for_a_fixed_outlet(): void
    {
        $response = $this->actingAs($this->kelilingEmployee())->postJson('/api/koleksi/keliling', ['jenis' => 'gerai', 'nama' => ' ', 'hari' => []] + $this->newStop());

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'nama' => 'Isi nama gerai.',
            'hari' => 'Pilih hari layanan.',
        ]);
    }

    public function test_requires_both_dates_for_a_temporary_counter(): void
    {
        $response = $this->actingAs($this->kelilingEmployee())->postJson('/api/koleksi/keliling', ['jenis' => 'sementara', 'nama' => 'Loket Pasar Oeba', 'tanggalMulai' => null, 'hari' => [1]] + $this->newStop());

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'tanggalMulai' => 'Isi tanggal mulai.',
            'tanggalSelesai' => 'Isi tanggal selesai.',
        ]);
    }

    #[TestWith(['2026-10-18', [1], 'tanggalSelesai', 'Tanggal selesai tidak boleh sebelum tanggal mulai.'])]
    #[TestWith(['2027-10-20', [1], 'tanggalSelesai', 'Loket sementara paling lama satu tahun.'])]
    #[TestWith(['2026-10-21', [0, 6], 'hari', 'Tidak ada hari terpilih di antara tanggal mulai dan selesai.'])]
    public function test_checks_the_dates_and_weekdays_of_a_temporary_counter(string $last, array $days, string $field, string $message): void
    {
        $response = $this->actingAs($this->kelilingEmployee())->postJson('/api/koleksi/keliling', [
            'jenis' => 'sementara',
            'nama' => 'Loket Pasar Oeba',
            'tanggalMulai' => '2026-10-19',
            'tanggalSelesai' => $last,
            'hari' => $days,
        ] + $this->newStop());

        $response->assertUnprocessable()->assertJsonValidationErrors([$field => $message]);
    }

    public function test_accepts_a_temporary_counter_whose_only_weekday_is_its_last_day(): void
    {
        $this->actingAs($this->kelilingEmployee())->postJson('/api/koleksi/keliling', [
            'jenis' => 'sementara',
            'nama' => 'Loket Pasar Oeba',
            'tanggalMulai' => '2026-10-19',
            'tanggalSelesai' => '2026-10-21',
            'hari' => [3],
        ] + $this->newStop())->assertCreated();
    }

    public function test_accepts_a_temporary_counter_running_just_under_a_year(): void
    {
        $this->actingAs($this->kelilingEmployee())->postJson('/api/koleksi/keliling', [
            'jenis' => 'sementara',
            'nama' => 'Loket Pasar Oeba',
            'tanggalMulai' => '2026-10-19',
            'tanggalSelesai' => '2027-10-19',
            'hari' => [1],
        ] + $this->newStop())->assertCreated();
    }

    #[TestWith([[7], 'hari.0', 'Hari layanan tidak valid.'])]
    #[TestWith([[-1], 'hari.0', 'Hari layanan tidak valid.'])]
    #[TestWith([['Senin'], 'hari.0', 'Hari layanan tidak valid.'])]
    #[TestWith([[1, 1], 'hari.1', 'Setiap hari cukup dipilih sekali.'])]
    public function test_rejects_weekdays_outside_sunday_to_saturday_or_repeated(array $days, string $field, string $message): void
    {
        $response = $this->actingAs($this->kelilingEmployee())->postJson('/api/koleksi/keliling', ['jenis' => 'gerai', 'nama' => 'Samsat Corner', 'hari' => $days] + $this->newStop());

        $response->assertUnprocessable()->assertJsonValidationErrors([$field => $message]);
    }

    #[TestWith(['17-10-2026'])]
    #[TestWith(['2026-02-30'])]
    public function test_rejects_a_date_that_is_not_yyyy_mm_dd(string $date): void
    {
        $response = $this->actingAs($this->kelilingEmployee())->postJson('/api/koleksi/keliling', ['tanggalMulai' => $date] + $this->newStop());

        $response->assertUnprocessable()->assertJsonValidationErrors(['tanggalMulai' => 'Tanggal tidak valid.']);
    }

    #[TestWith(['8:00'])]
    #[TestWith(['08.00'])]
    #[TestWith(['24:00'])]
    public function test_rejects_a_time_that_is_not_hh_mm(string $mulai): void
    {
        $response = $this->actingAs($this->kelilingEmployee())->postJson('/api/koleksi/keliling', ['mulai' => $mulai] + $this->newStop());

        $response->assertUnprocessable()->assertJsonValidationErrors(['mulai' => 'Jam mulai tidak valid.']);
    }

    #[TestWith(['06:00'])]
    #[TestWith(['05:30'])]
    public function test_requires_the_entry_to_end_after_it_starts(string $selesai): void
    {
        $response = $this->actingAs($this->kelilingEmployee())->postJson('/api/koleksi/keliling', ['mulai' => '06:00', 'selesai' => $selesai] + $this->newStop());

        $response->assertUnprocessable()->assertJsonValidationErrors(['selesai' => 'Jam selesai harus setelah jam mulai.']);
    }

    public function test_compares_a_changed_end_time_with_the_stored_start_time(): void
    {
        $stop = MobileServiceStop::factory()->create(['mulai' => '13:00', 'selesai' => '15:00']);

        $response = $this->actingAs($this->kelilingEmployee())->patchJson("/api/koleksi/keliling/{$stop->id}", ['selesai' => '12:00']);

        $response->assertUnprocessable()->assertJsonValidationErrors(['selesai' => 'Jam selesai harus setelah jam mulai.']);
        $this->assertSame('15:00', $stop->fresh()->selesai);
    }

    public function test_rejects_control_characters_and_invisible_text(): void
    {
        $response = $this->actingAs($this->kelilingEmployee())->postJson('/api/koleksi/keliling', [
            'lokasi' => "Kantor\u{0007}",
            'layanan' => "\u{0301}",
        ] + $this->newStop());

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'lokasi' => 'Lokasi berisi karakter kontrol yang tidak diizinkan.',
            'layanan' => 'Layanan tidak boleh hanya berisi spasi atau karakter tak terlihat.',
        ]);
    }

    public function test_rejects_a_place_or_name_longer_than_120_characters(): void
    {
        $response = $this->actingAs($this->kelilingEmployee())->postJson('/api/koleksi/keliling', ['jenis' => 'gerai', 'nama' => str_repeat('a', 121), 'hari' => [1], 'lokasi' => str_repeat('a', 121)] + $this->newStop());

        $response->assertUnprocessable()->assertJsonValidationErrors(['nama' => 'Nama maksimal 120 karakter.', 'lokasi' => 'Lokasi maksimal 120 karakter.']);
    }

    public function test_changes_only_the_fields_sent_and_logs_the_change(): void
    {
        $stop = MobileServiceStop::factory()->on('2026-10-21', '03')->create(['lokasi' => 'Kantor Kelurahan Penfui', 'aktif' => true]);
        $employee = $this->kelilingEmployee();

        $response = $this->actingAs($employee)->patchJson("/api/koleksi/keliling/{$stop->id}", ['aktif' => false]);

        $response->assertOk()->assertJsonPath('aktif', false)->assertJsonPath('lokasi', 'Kantor Kelurahan Penfui')->assertJsonPath('unit', '03');
        $this->assertFalse($stop->fresh()->aktif);
        $this->assertDatabaseHas('activities', [
            'user_id' => $employee->id,
            'module' => 'keliling',
            'action' => 'memperbarui',
            'target' => 'Samsat Keliling 03 · Rabu, 2026-10-21 · Kantor Kelurahan Penfui',
        ]);
    }

    public function test_moves_a_stop_to_another_date_and_its_weekday(): void
    {
        $stop = MobileServiceStop::factory()->on('2026-10-17')->create();

        $response = $this->actingAs($this->kelilingEmployee())->patchJson("/api/koleksi/keliling/{$stop->id}", ['tanggalMulai' => '2026-10-19']);

        $response->assertOk()->assertJsonPath('tanggalMulai', '2026-10-19')->assertJsonPath('tanggalSelesai', '2026-10-19')->assertJsonPath('hari', [1]);
    }

    public function test_checks_a_changed_kind_against_what_is_stored(): void
    {
        $stop = MobileServiceStop::factory()->on('2026-10-17')->create();

        $response = $this->actingAs($this->kelilingEmployee())->patchJson("/api/koleksi/keliling/{$stop->id}", ['jenis' => 'gerai']);

        $response->assertUnprocessable()->assertJsonValidationErrors(['nama' => 'Isi nama gerai.']);
        $this->assertSame('keliling', $stop->fresh()->jenis->value);
    }

    public function test_adds_a_typed_place_to_the_list_of_places_once(): void
    {
        ServiceLocation::factory()->create(['nama' => 'Taman Nostalgia', 'urutan' => 1]);
        $employee = $this->kelilingEmployee();

        $this->actingAs($employee)->postJson('/api/koleksi/keliling', $this->newStop())->assertCreated();
        $this->actingAs($employee)->postJson('/api/koleksi/keliling', ['lokasi' => 'taman nostalgia'] + $this->newStop())->assertCreated();

        $this->assertSame(['CFD Eltari', 'Taman Nostalgia'], ServiceLocation::query()->orderBy('nama')->pluck('nama')->all());
        $this->assertDatabaseHas('service_locations', ['nama' => 'CFD Eltari', 'kecamatan' => 'Kec. Oebobo', 'urutan' => 2]);
        $this->assertDatabaseHas('activities', ['user_id' => $employee->id, 'action' => 'menambahkan', 'target' => 'Lokasi CFD Eltari']);
        $this->assertDatabaseCount('activities', 3);
    }

    public function test_adds_a_changed_place_but_does_not_bring_back_a_removed_one(): void
    {
        $stop = MobileServiceStop::factory()->on('2026-10-17')->create(['lokasi' => 'Bundaran PU', 'kecamatan' => 'Kec. Oebobo']);
        $employee = $this->kelilingEmployee();

        $this->actingAs($employee)->patchJson("/api/koleksi/keliling/{$stop->id}", ['aktif' => false])->assertOk();
        $this->assertDatabaseCount('service_locations', 0);

        $this->actingAs($employee)->patchJson("/api/koleksi/keliling/{$stop->id}", ['lokasi' => 'Tamnos', 'kecamatan' => 'Kec. Kota Lama'])->assertOk();
        $this->assertDatabaseHas('service_locations', ['nama' => 'Tamnos', 'kecamatan' => 'Kec. Kota Lama']);
    }

    public function test_logs_nothing_when_nothing_changes(): void
    {
        $stop = MobileServiceStop::factory()->outlet()->create(['aktif' => true]);

        $this->actingAs($this->kelilingEmployee())->patchJson("/api/koleksi/keliling/{$stop->id}", ['aktif' => true, 'hari' => [5, 4, 3, 2, 1]])->assertOk();

        $this->assertDatabaseCount('activities', 0);
    }

    private function kelilingEmployee(): User
    {
        return User::factory()->withModules(DashboardModule::Keliling)->create();
    }

    /**
     * A Samsat Keliling stop on Saturday 17 October 2026.
     *
     * @return array<string, mixed>
     */
    private function newStop(): array
    {
        return [
            'jenis' => 'keliling',
            'unit' => '01',
            'tanggalMulai' => '2026-10-17',
            'mulai' => '06:00',
            'selesai' => '09:00',
            'lokasi' => 'CFD Eltari',
            'kecamatan' => 'Kec. Oebobo',
            'layanan' => 'Pajak tahunan',
            'catatan' => '',
            'aktif' => true,
        ];
    }
}
