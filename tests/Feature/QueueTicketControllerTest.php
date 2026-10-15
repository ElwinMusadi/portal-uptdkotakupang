<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Enums\QueueStage;
use App\Models\QueueService;
use App\Models\QueueTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class QueueTicketControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * Every stage, as services A and B go through them (D-051).
     *
     * @var list<string>
     */
    private const array ALL_STAGES = ['pendaftaran', 'penetapan', 'kwitansi', 'pembayaran', 'cetak-skpd', 'cetak-stnk', 'penyerahan'];

    public function test_returns_401_to_guests_and_403_to_employees_without_the_module(): void
    {
        $ticket = QueueTicket::factory()->create();
        $requests = [
            ['GET', '/api/antrean/tiket'],
            ['POST', '/api/antrean/tiket'],
            ['POST', "/api/antrean/tiket/{$ticket->id}/cetak-ulang"],
            ['POST', "/api/antrean/tiket/{$ticket->id}/batal"],
            ['GET', '/api/antrean/layanan'],
        ];

        foreach ($requests as [$method, $uri]) {
            $this->json($method, $uri)->assertUnauthorized();
        }

        $this->actingAs(User::factory()->withModules(DashboardModule::Papan)->create());
        foreach ($requests as [$method, $uri]) {
            $this->json($method, $uri)->assertForbidden()->assertExactJson(['message' => 'Modul Antrean Online belum ditugaskan kepada Anda.']);
        }
    }

    public function test_prints_the_next_number_of_the_service_for_the_wita_day(): void
    {
        $this->travelTo('2026-10-15 01:00:00');
        $employee = User::factory()->withModules(DashboardModule::Antrean)->create(['name' => 'Petugas Informasi']);

        $response = $this->actingAs($employee)->postJson('/api/antrean/tiket', ['nopol' => 'dh 1234-ab', 'layanan' => 'A']);

        $response->assertCreated()
            ->assertJsonPath('nomor', 'A-001')
            ->assertJsonPath('tanggal', '2026-10-15')
            ->assertJsonPath('layanan', ['kode' => 'A', 'nama' => 'Registrasi Ulang'])
            ->assertJsonPath('nopol', 'DH 1234 AB')
            ->assertJsonPath('status', 'menunggu')
            ->assertJsonPath('tahap', 'pendaftaran')
            ->assertJsonPath('tahapLayanan', self::ALL_STAGES)
            ->assertJsonPath('dicetak', '2026-10-15T01:00:00+00:00')
            ->assertJsonPath('dicetakOleh', 'Petugas Informasi')
            ->assertJsonPath('dibatalkan', null)
            ->assertJsonPath('alasanBatal', null);
        $ticket = QueueTicket::query()->sole();
        $this->assertSame('DH1234AB', $ticket->nopol);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{22}$/', $ticket->kode_lacak);
        $response->assertJsonPath('urlLacak', url("/lacak/{$ticket->kode_lacak}"));
        $this->assertDatabaseHas('queue_events', ['queue_ticket_id' => $ticket->id, 'aksi' => 'dicetak', 'tahap' => null, 'user_id' => $employee->id]);

        $this->actingAs($employee)->postJson('/api/antrean/tiket', ['nopol' => 'DH5678', 'layanan' => 'A'])->assertJsonPath('nomor', 'A-002');
        $this->actingAs($employee)->postJson('/api/antrean/tiket', ['nopol' => 'B1A', 'layanan' => 'B'])
            ->assertJsonPath('nomor', 'B-001')
            ->assertJsonPath('nopol', 'B 1 A');
        $this->assertNotSame(...QueueTicket::query()->pluck('kode_lacak')->take(2)->all());
    }

    public function test_starts_again_from_001_at_midnight_wita(): void
    {
        $employee = User::factory()->withModules(DashboardModule::Antrean)->create();

        // 23.59 WITA on 15 October, then 00.00 WITA on 16 October
        $this->travelTo('2026-10-15 15:59:00');
        $this->actingAs($employee)->postJson('/api/antrean/tiket', ['nopol' => 'DH1', 'layanan' => 'C'])
            ->assertJsonPath('nomor', 'C-001')
            ->assertJsonPath('tanggal', '2026-10-15');
        $this->travelTo('2026-10-15 16:00:00');
        $this->actingAs($employee)->postJson('/api/antrean/tiket', ['nopol' => 'DH2', 'layanan' => 'C'])
            ->assertJsonPath('nomor', 'C-001')
            ->assertJsonPath('tanggal', '2026-10-16');
    }

    public function test_gives_a_ticket_the_stages_of_its_service(): void
    {
        $employee = User::factory()->withModules(DashboardModule::Antrean)->create();
        QueueService::query()->where('kode', 'D')->update(['tahap' => json_encode(['penetapan', 'penyerahan'])]);

        $this->actingAs($employee)->postJson('/api/antrean/tiket', ['nopol' => 'DH1', 'layanan' => 'C'])
            ->assertJsonPath('tahapLayanan', ['pendaftaran', 'penetapan', 'kwitansi', 'pembayaran', 'cetak-skpd', 'penyerahan']);
        $this->actingAs($employee)->postJson('/api/antrean/tiket', ['nopol' => 'DH1', 'layanan' => 'D'])
            ->assertJsonPath('tahap', 'penetapan')
            ->assertJsonPath('tahapLayanan', ['penetapan', 'penyerahan']);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    #[TestWith([['layanan' => 'A'], 'nopol', 'Tulis nomor polisi kendaraan.'])]
    #[TestWith([['nopol' => ' - ', 'layanan' => 'A'], 'nopol', 'Tulis nomor polisi kendaraan.'])]
    #[TestWith([['nopol' => '1234', 'layanan' => 'A'], 'nopol', 'Tulis nomor polisi seperti DH 1234 AB.'])]
    #[TestWith([['nopol' => 'DHX 1234 AB', 'layanan' => 'A'], 'nopol', 'Tulis nomor polisi seperti DH 1234 AB.'])]
    #[TestWith([['nopol' => 'DH 12345 AB', 'layanan' => 'A'], 'nopol', 'Tulis nomor polisi seperti DH 1234 AB.'])]
    #[TestWith([['nopol' => ['DH1'], 'layanan' => 'A'], 'nopol', 'Tulis nomor polisi seperti DH 1234 AB.'])]
    #[TestWith([['nopol' => 'DH1'], 'layanan', 'Pilih layanan.'])]
    #[TestWith([['nopol' => 'DH1', 'layanan' => 'Z'], 'layanan', 'Layanan ini tidak ada atau sedang tidak aktif.'])]
    public function test_rejects_an_invalid_plate_or_service(array $input, string $field, string $message): void
    {
        $this->actingAs(User::factory()->withModules(DashboardModule::Antrean)->create())
            ->postJson('/api/antrean/tiket', $input)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$field => $message]);
        $this->assertDatabaseCount('queue_tickets', 0);
    }

    public function test_prints_no_number_for_an_inactive_service(): void
    {
        QueueService::query()->where('kode', 'D')->update(['aktif' => false]);

        $this->actingAs(User::factory()->withModules(DashboardModule::Antrean)->create())
            ->postJson('/api/antrean/tiket', ['nopol' => 'DH1', 'layanan' => 'D'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['layanan' => 'Layanan ini tidak ada atau sedang tidak aktif.']);
    }

    public function test_lists_the_numbers_printed_on_a_day_latest_first(): void
    {
        $this->travelTo('2026-10-15 03:00:00');
        $earlier = QueueTicket::factory()->create(['nomor' => 'A-001', 'created_at' => '2026-10-15 01:00:00']);
        $later = QueueTicket::factory()->create(['nomor' => 'A-002', 'created_at' => '2026-10-15 02:00:00']);
        $yesterday = QueueTicket::factory()->create(['tanggal' => '2026-10-14']);
        $employee = User::factory()->withModules(DashboardModule::Antrean)->create();

        $this->actingAs($employee)->getJson('/api/antrean/tiket')->assertOk()->assertJsonPath('*.id', [$later->id, $earlier->id]);
        $this->actingAs($employee)->getJson('/api/antrean/tiket?tanggal=2026-10-14')->assertOk()->assertJsonPath('*.id', [$yesterday->id]);
        $this->actingAs($employee)->getJson('/api/antrean/tiket?tanggal=14-10-2026')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tanggal' => 'Tanggal tidak valid.']);
    }

    public function test_prints_a_ticket_again_with_the_same_number_and_code(): void
    {
        $ticket = QueueTicket::factory()->create(['nomor' => 'A-007']);
        $employee = User::factory()->withModules(DashboardModule::Antrean)->create();

        $this->actingAs($employee)->postJson("/api/antrean/tiket/{$ticket->id}/cetak-ulang")
            ->assertOk()
            ->assertJsonPath('nomor', 'A-007')
            ->assertJsonPath('urlLacak', url("/lacak/{$ticket->kode_lacak}"));
        $this->assertDatabaseHas('queue_events', ['queue_ticket_id' => $ticket->id, 'aksi' => 'dicetak ulang', 'tahap' => 'pendaftaran', 'user_id' => $employee->id]);

        $cancelled = QueueTicket::factory()->cancelled()->create(['nomor' => 'A-008']);
        $this->actingAs($employee)->postJson("/api/antrean/tiket/{$cancelled->id}/cetak-ulang")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tiket' => 'Nomor A-008 sudah dibatalkan dan tidak bisa dicetak ulang.']);
    }

    public function test_cancels_a_number_with_the_reason_once(): void
    {
        $this->travelTo('2026-10-15 02:30:00');
        $ticket = QueueTicket::factory()->create(['nomor' => 'B-003', 'tahap' => QueueStage::Pendaftaran]);
        $employee = User::factory()->withModules(DashboardModule::Antrean)->create();

        $this->actingAs($employee)->postJson("/api/antrean/tiket/{$ticket->id}/batal", ['alasan' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['alasan' => 'Tulis alasan pembatalan.']);
        $this->actingAs($employee)->postJson("/api/antrean/tiket/{$ticket->id}/batal", ['alasan' => 'Salah memilih layanan.'])
            ->assertOk()
            ->assertJsonPath('status', 'batal')
            ->assertJsonPath('tahap', 'pendaftaran')
            ->assertJsonPath('alasanBatal', 'Salah memilih layanan.')
            ->assertJsonPath('dibatalkan', '2026-10-15T02:30:00+00:00');
        $this->assertDatabaseHas('queue_events', [
            'queue_ticket_id' => $ticket->id, 'aksi' => 'dibatalkan', 'tahap' => 'pendaftaran', 'keterangan' => 'Salah memilih layanan.', 'user_id' => $employee->id,
        ]);

        $this->actingAs($employee)->postJson("/api/antrean/tiket/{$ticket->id}/batal", ['alasan' => 'Lagi.'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tiket' => 'Nomor B-003 sudah dibatalkan.']);
        $this->assertDatabaseCount('queue_events', 1);
    }

    public function test_lets_administrators_print_without_the_module_assigned(): void
    {
        $this->actingAs(User::factory()->administrator()->create())
            ->postJson('/api/antrean/tiket', ['nopol' => 'DH1', 'layanan' => 'A'])
            ->assertCreated();
    }
}
