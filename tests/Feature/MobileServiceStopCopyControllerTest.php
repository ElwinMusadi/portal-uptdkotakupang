<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\MobileServiceStop;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class MobileServiceStopCopyControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Friday 16 October 2026 in Kupang, when next week's schedule is prepared
        $this->travelTo(Carbon::parse('2026-10-16 10:00:00', 'Asia/Makassar'));
    }

    public function test_returns_401_to_guests_and_403_to_employees_without_the_module(): void
    {
        $this->postJson('/api/koleksi/keliling/salin-minggu', ['dari' => '2026-10-12'])->assertUnauthorized();

        $this->actingAs(User::factory()->withModules(DashboardModule::Pemutakhiran)->create())
            ->postJson('/api/koleksi/keliling/salin-minggu', ['dari' => '2026-10-12'])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Modul Layanan Luar Kantor belum ditugaskan kepada Anda.']);
    }

    public function test_copies_a_weeks_stops_to_the_same_weekdays_of_next_week_switched_off(): void
    {
        MobileServiceStop::factory()->on('2026-10-17', '01')->create(['mulai' => '06:00', 'selesai' => '09:00', 'lokasi' => 'CFD Eltari', 'catatan' => 'Bawa STNK.', 'urutan' => 4]);
        MobileServiceStop::factory()->on('2026-10-12', '02')->inactive()->create(['lokasi' => 'Kantor Kelurahan Oesapa']);
        MobileServiceStop::factory()->on('2026-10-11', '03')->create(['lokasi' => 'Minggu sebelumnya']);
        MobileServiceStop::factory()->on('2026-10-19', '03')->create(['lokasi' => 'Sudah minggu depan']);
        MobileServiceStop::factory()->outlet()->create(['lokasi' => 'Lippo Plaza']);
        MobileServiceStop::factory()->counter('2026-10-12', '2026-10-16')->create(['lokasi' => 'Kantor Lurah Oebobo']);
        $employee = User::factory()->withModules(DashboardModule::Keliling)->create();

        $response = $this->actingAs($employee)->postJson('/api/koleksi/keliling/salin-minggu', ['dari' => '2026-10-12']);

        $response->assertOk()->assertJsonCount(2);
        $this->assertSame(['2026-10-19', '2026-10-24'], $response->json('*.tanggalMulai'));
        $this->assertSame(['Kantor Kelurahan Oesapa', 'CFD Eltari'], $response->json('*.lokasi'));
        $response->assertJsonPath('1.unit', '01')
            ->assertJsonPath('1.tanggalSelesai', '2026-10-24')
            ->assertJsonPath('1.hari', [6])
            ->assertJsonPath('1.mulai', '06:00')
            ->assertJsonPath('1.catatan', 'Bawa STNK.')
            ->assertJsonPath('0.aktif', false)
            ->assertJsonPath('1.aktif', false)
            ->assertJsonPath('0.urutan', 5)
            ->assertJsonPath('1.urutan', 6);
        $this->assertDatabaseCount('mobile_service_stops', 8);
        $this->assertDatabaseHas('activities', [
            'user_id' => $employee->id,
            'module' => 'keliling',
            'action' => 'menyalin',
            'target' => '2 jadwal Samsat Keliling ke minggu 2026-10-19',
        ]);
    }

    public function test_does_not_copy_a_stop_already_scheduled_next_week(): void
    {
        MobileServiceStop::factory()->on('2026-10-17', '01')->create(['mulai' => '06:00', 'lokasi' => 'CFD Eltari']);
        MobileServiceStop::factory()->on('2026-10-24', '01')->inactive()->create(['mulai' => '06:00', 'lokasi' => 'CFD Eltari']);
        MobileServiceStop::factory()->on('2026-10-17', '02')->create(['mulai' => '17:00', 'selesai' => '19:00', 'lokasi' => 'Tamnos']);
        // Another unit, another start time or another place on the same day is not the same stop
        MobileServiceStop::factory()->on('2026-10-24', '03')->create(['mulai' => '17:00', 'selesai' => '19:00', 'lokasi' => 'Tamnos']);
        MobileServiceStop::factory()->on('2026-10-24', '02')->create(['mulai' => '15:00', 'selesai' => '16:30', 'lokasi' => 'Tamnos']);
        MobileServiceStop::factory()->on('2026-10-24', '02')->create(['mulai' => '17:00', 'selesai' => '19:00', 'lokasi' => 'Taman Nostalgia']);
        $employee = User::factory()->withModules(DashboardModule::Keliling)->create();

        $response = $this->actingAs($employee)->postJson('/api/koleksi/keliling/salin-minggu', ['dari' => '2026-10-12']);

        $response->assertOk()->assertJsonCount(1)->assertJsonPath('0.unit', '02');
        $this->actingAs($employee)->postJson('/api/koleksi/keliling/salin-minggu', ['dari' => '2026-10-12'])->assertOk()->assertExactJson([]);
        $this->assertDatabaseCount('activities', 1);
    }

    public function test_copies_a_days_stops_to_the_chosen_days_keeping_each_ones_display_setting(): void
    {
        MobileServiceStop::factory()->on('2026-10-19', '01')->create(['mulai' => '09:00', 'selesai' => '12:00', 'lokasi' => 'Bundaran PU', 'catatan' => 'Bawa STNK.']);
        MobileServiceStop::factory()->on('2026-10-19', '02')->inactive()->create(['mulai' => '13:00', 'selesai' => '15:00', 'lokasi' => 'Kantor Kelurahan Oesapa']);
        MobileServiceStop::factory()->on('2026-10-20', '03')->create(['lokasi' => 'Hari lain']);
        MobileServiceStop::factory()->outlet()->create(['lokasi' => 'Lippo Plaza']);
        MobileServiceStop::factory()->counter('2026-10-19', '2026-10-23')->create(['lokasi' => 'Kantor Lurah Oebobo']);
        $employee = User::factory()->withModules(DashboardModule::Keliling)->create();

        $response = $this->actingAs($employee)->postJson('/api/koleksi/keliling/salin-hari', ['dari' => '2026-10-19', 'ke' => ['2026-10-21', '2026-10-20']]);

        $response->assertOk()->assertJsonCount(4);
        $this->assertSame(
            ['2026-10-20 01 Bundaran PU', '2026-10-20 02 Kantor Kelurahan Oesapa', '2026-10-21 01 Bundaran PU', '2026-10-21 02 Kantor Kelurahan Oesapa'],
            array_map(fn (array $copy): string => "{$copy['tanggalMulai']} {$copy['unit']} {$copy['lokasi']}", $response->json()),
        );
        $response->assertJsonPath('0.tanggalSelesai', '2026-10-20')
            ->assertJsonPath('0.hari', [2])
            ->assertJsonPath('2.hari', [3])
            ->assertJsonPath('0.mulai', '09:00')
            ->assertJsonPath('0.catatan', 'Bawa STNK.')
            ->assertJsonPath('0.aktif', true)
            ->assertJsonPath('1.aktif', false);
        $this->assertDatabaseHas('activities', ['user_id' => $employee->id, 'action' => 'menyalin', 'target' => '4 jadwal Samsat Keliling dari 2026-10-19']);
    }

    public function test_does_not_copy_a_day_onto_a_stop_already_there(): void
    {
        MobileServiceStop::factory()->on('2026-10-19', '01')->create(['mulai' => '09:00', 'lokasi' => 'Bundaran PU']);
        MobileServiceStop::factory()->on('2026-10-20', '01')->create(['mulai' => '09:00', 'lokasi' => 'Bundaran PU']);
        $employee = User::factory()->withModules(DashboardModule::Keliling)->create();

        $this->actingAs($employee)->postJson('/api/koleksi/keliling/salin-hari', ['dari' => '2026-10-19', 'ke' => ['2026-10-20']])
            ->assertOk()
            ->assertExactJson([]);
        $this->assertDatabaseCount('mobile_service_stops', 2);
        $this->assertDatabaseCount('activities', 0);
    }

    #[TestWith([[], 'dari', 'Pilih hari yang disalin.'])]
    #[TestWith([['dari' => '2026-10-19'], 'ke', 'Pilih minimal satu hari tujuan.'])]
    #[TestWith([['dari' => '2026-10-19', 'ke' => ['20-10-2026']], 'ke.0', 'Tanggal tujuan tidak valid.'])]
    #[TestWith([['dari' => '2026-10-19', 'ke' => ['2026-10-19']], 'ke.0', 'Hari tujuan harus berbeda dari hari asal.'])]
    #[TestWith([['dari' => '2026-10-19', 'ke' => ['2026-10-20', '2026-10-20']], 'ke.1', 'Setiap hari tujuan cukup dipilih sekali.'])]
    #[TestWith([['dari' => '2026-10-19', 'ke' => ['2026-10-20', '2026-10-21', '2026-10-22', '2026-10-23', '2026-10-24', '2026-10-25', '2026-10-26', '2026-10-27', '2026-10-28', '2026-10-29', '2026-10-30', '2026-10-31', '2026-11-01', '2026-11-02', '2026-11-03']], 'ke', 'Paling banyak 14 hari tujuan sekali salin.'])]
    public function test_requires_a_day_and_up_to_14_other_days(array $request, string $field, string $message): void
    {
        $response = $this->actingAs(User::factory()->administrator()->create())->postJson('/api/koleksi/keliling/salin-hari', $request);

        $response->assertUnprocessable()->assertJsonValidationErrors([$field => $message]);
    }

    public function test_forbids_copying_days_without_the_module(): void
    {
        $this->postJson('/api/koleksi/keliling/salin-hari', ['dari' => '2026-10-19', 'ke' => ['2026-10-20']])->assertUnauthorized();
        $this->actingAs(User::factory()->withModules(DashboardModule::Pemutakhiran)->create())
            ->postJson('/api/koleksi/keliling/salin-hari', ['dari' => '2026-10-19', 'ke' => ['2026-10-20']])
            ->assertForbidden();
    }

    #[TestWith([[], 'Pilih minggu yang disalin.'])]
    #[TestWith([['dari' => '12-10-2026'], 'Tanggal awal minggu tidak valid.'])]
    #[TestWith([['dari' => '2026-10-13'], 'Minggu yang disalin harus dimulai hari Senin.'])]
    public function test_requires_the_monday_of_the_week_to_copy(array $request, string $message): void
    {
        $response = $this->actingAs(User::factory()->administrator()->create())->postJson('/api/koleksi/keliling/salin-minggu', $request);

        $response->assertUnprocessable()->assertJsonValidationErrors(['dari' => $message]);
    }
}
