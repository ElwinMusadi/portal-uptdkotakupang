<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\MobileServiceStop;
use App\Models\ServiceBoardOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ServiceBoardOrderControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Friday 16 October 2026 in Kupang
        $this->travelTo(Carbon::parse('2026-10-16 10:00:00', 'Asia/Makassar'));
    }

    public function test_returns_401_to_guests_and_403_to_employees_who_do_not_manage_the_module(): void
    {
        $this->getJson('/api/koleksi/keliling/urutan-papan')->assertUnauthorized();
        $this->putJson('/api/koleksi/keliling/urutan-papan/2026-10-17', ['ids' => []])->assertUnauthorized();

        $this->actingAs(User::factory()->withModules(DashboardModule::Faq)->create())
            ->getJson('/api/koleksi/keliling/urutan-papan')
            ->assertForbidden();

        // Jadwal Pemutakhiran may read the schedule but not order it
        $monitor = User::factory()->withModules(DashboardModule::Pemutakhiran)->create();
        $this->actingAs($monitor)->getJson('/api/koleksi/keliling/urutan-papan')->assertOk();
        $this->actingAs($monitor)
            ->putJson('/api/koleksi/keliling/urutan-papan/2026-10-17', ['ids' => []])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Modul Layanan Luar Kantor belum ditugaskan kepada Anda.']);
        $this->assertDatabaseCount('activities', 0);
    }

    public function test_lists_the_orders_from_today_earliest_first(): void
    {
        $stop = MobileServiceStop::factory()->outlet('Samsat Corner', [0, 1, 2, 3, 4, 5, 6])->create();
        ServiceBoardOrder::factory()->of('2026-10-19', [$stop])->create();
        ServiceBoardOrder::factory()->of('2026-10-16', [$stop])->create();
        ServiceBoardOrder::factory()->of('2026-10-15', [$stop])->create();

        $this->actingAs(User::factory()->withModules(DashboardModule::Keliling)->create())
            ->getJson('/api/koleksi/keliling/urutan-papan')
            ->assertOk()
            ->assertExactJson([
                ['tanggal' => '2026-10-16', 'urutan' => [$stop->id]],
                ['tanggal' => '2026-10-19', 'urutan' => [$stop->id]],
            ]);
    }

    public function test_saves_and_replaces_a_dates_order_with_one_activity_entry_each_time(): void
    {
        // Saturday 17 October: a unit, an outlet and a temporary counter that is switched off
        $unit = MobileServiceStop::factory()->on('2026-10-17', '01')->create(['urutan' => 7]);
        $outlet = MobileServiceStop::factory()->outlet('Samsat Corner', [6])->create();
        $counter = MobileServiceStop::factory()->counter('2026-10-12', '2026-10-18', 'Loket Pameran', [6])->inactive()->create();
        $employee = User::factory()->withModules(DashboardModule::Keliling)->create();

        $this->actingAs($employee)
            ->putJson('/api/koleksi/keliling/urutan-papan/2026-10-17', ['ids' => [$outlet->id, $counter->id, $unit->id]])
            ->assertOk()
            ->assertExactJson(['tanggal' => '2026-10-17', 'urutan' => [$outlet->id, $counter->id, $unit->id]]);
        $this->actingAs($employee)
            ->putJson('/api/koleksi/keliling/urutan-papan/2026-10-17', ['ids' => [$unit->id]])
            ->assertOk()
            ->assertExactJson(['tanggal' => '2026-10-17', 'urutan' => [$unit->id]]);

        $this->assertDatabaseCount('service_board_orders', 1);
        $this->assertSame([$unit->id], ServiceBoardOrder::query()->sole()->urutan);
        // The entries themselves are not edited
        $this->assertSame(7, $unit->fresh()->urutan);
        $this->assertDatabaseHas('activities', [
            'user_id' => $employee->id,
            'module' => 'keliling',
            'action' => 'mengubah urutan',
            'target' => '3 entri papan Beranda 2026-10-17',
        ]);
        $this->assertDatabaseHas('activities', ['action' => 'mengubah urutan', 'target' => '1 entri papan Beranda 2026-10-17']);
        $this->assertDatabaseCount('activities', 2);
    }

    public function test_an_empty_list_puts_the_date_back_in_time_order(): void
    {
        $outlet = MobileServiceStop::factory()->outlet('Samsat Corner', [6])->create();
        ServiceBoardOrder::factory()->of('2026-10-17', [$outlet])->create();
        ServiceBoardOrder::factory()->of('2026-10-24', [$outlet])->create();
        $employee = User::factory()->withModules(DashboardModule::Keliling)->create();

        $this->actingAs($employee)
            ->putJson('/api/koleksi/keliling/urutan-papan/2026-10-17', ['ids' => []])
            ->assertOk()
            ->assertExactJson(['tanggal' => '2026-10-17', 'urutan' => []]);

        $this->assertDatabaseMissing('service_board_orders', ['tanggal' => '2026-10-17']);
        $this->assertDatabaseCount('service_board_orders', 1);
        $this->assertDatabaseHas('activities', [
            'user_id' => $employee->id,
            'action' => 'mengubah urutan',
            'target' => 'papan Beranda 2026-10-17 kembali urut jam',
        ]);
    }

    public function test_rejects_entries_that_are_not_served_on_the_date(): void
    {
        $served = MobileServiceStop::factory()->on('2026-10-17', '01')->create();
        $otherDay = MobileServiceStop::factory()->on('2026-10-18', '02')->create();
        $weekdaysOnly = MobileServiceStop::factory()->outlet('Mall Pelayanan Publik', [1, 2, 3, 4, 5])->create();
        $ended = MobileServiceStop::factory()->counter('2026-10-12', '2026-10-16', 'Loket Pasar', [6])->create();
        $employee = User::factory()->withModules(DashboardModule::Keliling)->create();

        foreach ([$otherDay->id, $weekdaysOnly->id, $ended->id, '01kq0000000000000000000000'] as $stranger) {
            $this->actingAs($employee)
                ->putJson('/api/koleksi/keliling/urutan-papan/2026-10-17', ['ids' => [$served->id, $stranger]])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['ids' => 'Urutan tidak cocok dengan jadwal terbaru. Muat ulang halaman lalu coba lagi.']);
        }

        $this->assertDatabaseCount('service_board_orders', 0);
        $this->assertDatabaseCount('activities', 0);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    #[TestWith(['2026-10-15', ['ids' => []], 'tanggal', 'Urutan papan hanya bisa diatur untuk hari ini dan hari berikutnya.'])]
    #[TestWith(['17-10-2026', ['ids' => []], 'tanggal', 'Tanggal tidak valid.'])]
    #[TestWith(['2026-10-17', [], 'ids', 'Daftar urutan tidak dikirim.'])]
    #[TestWith(['2026-10-17', ['ids' => 'semua'], 'ids', 'Daftar urutan tidak valid.'])]
    #[TestWith(['2026-10-17', ['ids' => [7]], 'ids.0', 'Daftar urutan tidak valid.'])]
    #[TestWith(['2026-10-17', ['ids' => ['bukan-id']], 'ids.0', 'Daftar urutan tidak valid.'])]
    public function test_rejects_an_invalid_date_or_list(string $date, array $body, string $field, string $message): void
    {
        $this->actingAs(User::factory()->withModules(DashboardModule::Keliling)->create())
            ->putJson("/api/koleksi/keliling/urutan-papan/{$date}", $body)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$field => $message]);
        $this->assertDatabaseCount('service_board_orders', 0);
    }

    public function test_rejects_an_entry_listed_twice_or_a_list_too_long(): void
    {
        $stop = MobileServiceStop::factory()->on('2026-10-17', '01')->create();
        $employee = User::factory()->withModules(DashboardModule::Keliling)->create();

        $this->actingAs($employee)
            ->putJson('/api/koleksi/keliling/urutan-papan/2026-10-17', ['ids' => [$stop->id, $stop->id]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ids.0' => 'Daftar urutan berisi entri ganda.']);
        $this->actingAs($employee)
            ->putJson('/api/koleksi/keliling/urutan-papan/2026-10-17', ['ids' => array_fill(0, 201, $stop->id)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ids' => 'Daftar urutan terlalu panjang.']);
    }

    #[TestWith(['2026-10-16 23:30:00', 200])]
    #[TestWith(['2026-10-17 00:30:00', 422])]
    public function test_accepts_today_in_kupang_time(string $now, int $status): void
    {
        $this->travelTo(Carbon::parse($now, 'Asia/Makassar'));
        $outlet = MobileServiceStop::factory()->outlet('Samsat Corner', [5])->create();

        $this->actingAs(User::factory()->withModules(DashboardModule::Keliling)->create())
            ->putJson('/api/koleksi/keliling/urutan-papan/2026-10-16', ['ids' => [$outlet->id]])
            ->assertStatus($status);
    }

    #[TestWith(['2026-10-16 23:30:00', 1])]
    #[TestWith(['2026-10-17 00:30:00', 0])]
    public function test_lists_from_today_in_kupang_time(string $now, int $count): void
    {
        $this->travelTo(Carbon::parse($now, 'Asia/Makassar'));
        ServiceBoardOrder::factory()->of('2026-10-16', [])->create();

        $this->actingAs(User::factory()->withModules(DashboardModule::Keliling)->create())
            ->getJson('/api/koleksi/keliling/urutan-papan')
            ->assertOk()
            ->assertJsonCount($count);
    }
}
