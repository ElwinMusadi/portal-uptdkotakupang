<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ServiceBoardControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[TestWith(['GET'])]
    #[TestWith(['PUT'])]
    public function test_returns_401_to_guests(string $method): void
    {
        $this->json($method, '/api/pengaturan/papan', $this->board())->assertUnauthorized();
    }

    #[TestWith(['GET'])]
    #[TestWith(['PUT'])]
    public function test_forbids_employees_without_the_module_with_403(string $method): void
    {
        $employee = User::factory()->withModules(DashboardModule::Jam)->create();

        $response = $this->actingAs($employee)->json($method, '/api/pengaturan/papan', $this->board());

        $response->assertForbidden()->assertExactJson(['message' => 'Modul Papan Layanan belum ditugaskan kepada Anda.']);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_shows_an_empty_board_until_it_is_first_saved(): void
    {
        $response = $this->actingAs($this->papanEmployee())->getJson('/api/pengaturan/papan');

        $response->assertOk()->assertExactJson([
            'nomorAntrean' => '',
            'layananAntrean' => '',
            'waktuTunggu' => 0,
            'selisihKemarin' => 0,
            'loket' => [],
            'catatan' => '',
            'diperbarui' => null,
        ]);
    }

    public function test_saves_the_board_stamped_by_the_server_and_logs_it(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 07:45:00', 'Asia/Makassar'));
        $employee = $this->papanEmployee();

        $response = $this->actingAs($employee)->putJson('/api/pengaturan/papan', ['diperbarui' => '2020-01-01T00:00:00Z', 'catatan' => null] + $this->board());

        $response->assertOk()
            ->assertJsonPath('diperbarui', '2026-10-13T23:45:00+00:00')
            ->assertJsonPath('catatan', '')
            ->assertJsonPath('loket.0', ['id' => 'l1', 'kode' => 'L1', 'nama' => 'Pajak tahunan', 'status' => 'buka']);
        $this->assertSame($response->json(), Setting::serviceBoard());
        $this->assertDatabaseHas('activities', [
            'user_id' => $employee->id,
            'module' => 'papan',
            'action' => 'memperbarui',
            'target' => 'Papan Layanan',
        ]);
    }

    public function test_keeps_the_previous_time_and_logs_nothing_when_the_content_is_unchanged(): void
    {
        $employee = $this->papanEmployee();
        $this->travelTo(Carbon::parse('2026-10-14 07:45:00', 'UTC'));
        $this->actingAs($employee)->putJson('/api/pengaturan/papan', $this->board())->assertOk();
        $this->travel(2)->hours();

        $response = $this->actingAs($employee)->putJson('/api/pengaturan/papan', $this->board());

        $response->assertOk()->assertJsonPath('diperbarui', '2026-10-14T07:45:00+00:00');
        $this->assertDatabaseCount('activities', 1);
    }

    public function test_validates_the_queue_wait_counters_and_note(): void
    {
        $board = $this->board();
        $board['nomorAntrean'] = '';
        $board['waktuTunggu'] = 601;
        $board['loket'][1]['status'] = 'libur';
        $board['loket'][1]['id'] = 'l1';
        $board['catatan'] = "Catatan\u{0007}";

        $response = $this->actingAs($this->papanEmployee())->putJson('/api/pengaturan/papan', $board);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'nomorAntrean' => 'Nomor antrean wajib diisi.',
            'waktuTunggu' => 'Waktu tunggu 0 sampai 600 menit.',
            'loket.1.status' => 'Status loket tidak valid.',
            'loket.1.id' => 'Loket tercantum dua kali.',
            'catatan' => 'Catatan berisi karakter kontrol yang tidak diizinkan.',
        ]);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_accepts_a_board_without_counters(): void
    {
        $response = $this->actingAs($this->papanEmployee())->putJson('/api/pengaturan/papan', ['loket' => []] + $this->board());

        $response->assertOk()->assertJsonPath('loket', []);
    }

    private function papanEmployee(): User
    {
        return User::factory()->withModules(DashboardModule::Papan)->create();
    }

    /**
     * @return array{nomorAntrean: string, layananAntrean: string, waktuTunggu: int, selisihKemarin: int, loket: list<array{id: string, kode: string, nama: string, status: string}>, catatan: string}
     */
    private function board(): array
    {
        return [
            'nomorAntrean' => 'A-042',
            'layananAntrean' => 'Loket 1 · Pajak tahunan',
            'waktuTunggu' => 18,
            'selisihKemarin' => -6,
            'loket' => [
                ['id' => 'l1', 'kode' => 'L1', 'nama' => 'Pajak tahunan', 'status' => 'buka'],
                ['id' => 'l2', 'kode' => 'L2', 'nama' => 'Mutasi & balik nama', 'status' => 'istirahat'],
            ],
            'catatan' => 'Diperbarui petugas setiap hari kerja.',
        ];
    }
}
