<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Enums\QueueStage;
use App\Http\Controllers\Dashboard\QueueArchiveController;
use App\Models\Activity;
use App\Models\QueueCounter;
use App\Models\QueueEvent;
use App\Models\QueueTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class QueueArchiveTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-15 03:00:00');
        $this->employee = User::factory()->withModules(DashboardModule::Antrean)->create(['name' => 'Petugas Arsip']);
    }

    public function test_returns_401_to_guests_and_403_to_employees_without_the_module(): void
    {
        $ticket = QueueTicket::factory()->create();
        $requests = [
            ['GET', '/api/antrean/arsip'],
            ['GET', "/api/antrean/tiket/{$ticket->id}"],
            ['GET', '/api/antrean/ringkasan'],
            ['GET', '/api/antrean/ringkasan/tiket'],
        ];

        foreach ([...$requests, ['DELETE', "/api/antrean/tiket/{$ticket->id}"]] as [$method, $uri]) {
            $this->json($method, $uri)->assertUnauthorized();
        }
        $this->actingAs(User::factory()->withModules(DashboardModule::Papan)->create());
        foreach ($requests as [$method, $uri]) {
            $this->json($method, $uri)->assertForbidden()->assertExactJson(['message' => 'Modul Antrean Online belum ditugaskan kepada Anda.']);
        }
    }

    public function test_lists_the_newest_tickets_of_every_day_and_status(): void
    {
        $newest = QueueTicket::factory()->create(['created_at' => '2026-10-15 02:00:00', 'nopol' => null]);
        $cancelled = QueueTicket::factory()->cancelled()->create(['created_at' => '2026-10-15 01:00:00']);
        $older = QueueTicket::factory()->create(['tanggal' => '2026-10-14', 'created_at' => '2026-10-14 01:00:00', 'status' => 'selesai', 'tahap' => null]);

        $this->search()->assertOk()->assertJsonPath('*.id', [$newest->id, $cancelled->id, $older->id])->assertJsonMissingPath('0.riwayat');
    }

    public function test_finds_a_ticket_by_its_qr_its_number_on_any_day_or_part_of_the_plate(): void
    {
        $ticket = QueueTicket::factory()->create(['nomor' => 'A-001', 'nopol' => 'DH1234AB', 'status' => 'selesai', 'tahap' => null]);
        $sameNumber = QueueTicket::factory()->create(['nomor' => 'A-001', 'tanggal' => '2026-10-14', 'nopol' => 'DH9999ZZ']);
        $other = QueueTicket::factory()->create(['nomor' => 'A-002', 'nopol' => 'EB1234CD']);

        $this->search(['q' => $ticket->trackingUrl()])->assertJsonPath('*.id', [$ticket->id]);
        $this->search(['q' => 'a1'])->assertJsonPath('*.id', [$ticket->id, $sameNumber->id]);
        $this->search(['q' => '1234'])->assertJsonPath('*.id', [$other->id, $ticket->id]);
        $this->search(['q' => 'dh 1234'])->assertJsonPath('*.id', [$ticket->id]);
        $this->search(['q' => '  '])->assertJsonCount(3);
    }

    public function test_narrows_by_dates_service_status_and_stage(): void
    {
        $day14 = QueueTicket::factory()->create(['tanggal' => '2026-10-14']);
        $day13 = QueueTicket::factory()->create(['tanggal' => '2026-10-13']);
        $serviceB = QueueTicket::factory()->of('B')->create();
        $done = QueueTicket::factory()->create(['status' => 'selesai', 'tahap' => null]);
        $atReceipt = QueueTicket::factory()->waitingAt(QueueStage::Kwitansi)->create();

        $this->search(['dari' => '2026-10-14'])->assertJsonCount(4)->assertJsonMissing(['id' => $day13->id]);
        $this->search(['sampai' => '2026-10-14'])->assertJsonPath('*.id', [$day14->id, $day13->id]);
        $this->search(['dari' => '2026-10-13', 'sampai' => '2026-10-13'])->assertJsonPath('*.id', [$day13->id]);
        $this->search(['layanan' => 'B'])->assertJsonPath('*.id', [$serviceB->id]);
        $this->search(['status' => 'selesai'])->assertJsonPath('*.id', [$done->id]);
        $this->search(['tahap' => 'kwitansi'])->assertJsonPath('*.id', [$atReceipt->id]);
    }

    public function test_refuses_wrong_filters(): void
    {
        $this->search(['dari' => '2026-10-15', 'sampai' => '2026-10-14'])->assertUnprocessable()->assertJsonValidationErrors(['sampai' => 'Tanggal akhir tidak boleh sebelum tanggal awal.']);
        $this->search(['dari' => '15-10-2026'])->assertUnprocessable()->assertJsonValidationErrors(['dari' => 'Tanggal awal tidak valid.']);
        $this->search(['layanan' => 'Z'])->assertUnprocessable()->assertJsonValidationErrors(['layanan' => 'Layanan ini tidak ada.']);
        $this->search(['status' => 'hilang'])->assertUnprocessable()->assertJsonValidationErrors(['status' => 'Status ini tidak ada.']);
        $this->search(['tahap' => 'kasir'])->assertUnprocessable()->assertJsonValidationErrors(['tahap' => 'Tahap ini tidak ada.']);
    }

    public function test_lists_at_most_two_hundred_tickets(): void
    {
        $oldest = QueueTicket::factory()->create(['created_at' => '2026-10-15 00:00:00']);
        QueueTicket::factory()->count(QueueArchiveController::LISTED)->create(['created_at' => '2026-10-15 01:00:00']);

        $this->search()->assertJsonCount(QueueArchiveController::LISTED)->assertJsonMissing(['id' => $oldest->id]);
    }

    public function test_shows_a_ticket_with_its_whole_history(): void
    {
        $receipt = QueueCounter::query()->where('tahap', 'kwitansi')->sole();
        $ticket = QueueTicket::factory()->waitingAt(QueueStage::Kwitansi)->create(['nomor' => 'A-007']);
        $events = [
            ['aksi' => 'dicetak', 'created_at' => '2026-10-15 01:00:00'],
            ['aksi' => 'tahap selesai', 'tahap' => 'pendaftaran', 'otomatis' => true, 'created_at' => '2026-10-15 01:30:00'],
            ['aksi' => 'dipanggil', 'tahap' => 'kwitansi', 'queue_counter_id' => $receipt->id, 'user_id' => $this->employee->id, 'created_at' => '2026-10-15 02:00:00'],
            ['aksi' => 'diurungkan', 'tahap' => 'penetapan', 'keterangan' => 'tahap selesai', 'user_id' => $this->employee->id, 'created_at' => '2026-10-15 02:10:00'],
        ];
        foreach ($events as $event) {
            $ticket->events()->forceCreate(['queue_ticket_id' => $ticket->id, ...$event]);
        }

        $this->actingAs($this->employee)->getJson("/api/antrean/tiket/{$ticket->id}")
            ->assertOk()
            ->assertJsonPath('nomor', 'A-007')
            ->assertJsonPath('riwayat', [
                ['aksi' => 'dicetak', 'tahap' => null, 'keterangan' => null, 'loket' => null, 'petugas' => null, 'otomatis' => false, 'waktu' => '2026-10-15T01:00:00+00:00'],
                ['aksi' => 'tahap selesai', 'tahap' => 'pendaftaran', 'keterangan' => null, 'loket' => null, 'petugas' => null, 'otomatis' => true, 'waktu' => '2026-10-15T01:30:00+00:00'],
                ['aksi' => 'dipanggil', 'tahap' => 'kwitansi', 'keterangan' => null, 'loket' => 'Loket Kwitansi', 'petugas' => 'Petugas Arsip', 'otomatis' => false, 'waktu' => '2026-10-15T02:00:00+00:00'],
                ['aksi' => 'diurungkan', 'tahap' => 'penetapan', 'keterangan' => 'tahap selesai', 'loket' => null, 'petugas' => 'Petugas Arsip', 'otomatis' => false, 'waktu' => '2026-10-15T02:10:00+00:00'],
            ]);
        $this->getJson('/api/antrean/tiket/01K7M00000000000000000000M')->assertNotFound();
    }

    public function test_administrators_delete_a_finished_ticket_of_an_earlier_day_with_its_history(): void
    {
        $ticket = QueueTicket::factory()->create(['nomor' => 'B-005', 'tanggal' => '2026-10-14', 'status' => 'selesai', 'tahap' => null]);
        $ticket->events()->create(['aksi' => 'dicetak']);
        $kept = QueueTicket::factory()->cancelled()->create(['tanggal' => '2026-10-14']);
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($this->employee)->deleteJson("/api/antrean/tiket/{$ticket->id}")
            ->assertForbidden()
            ->assertExactJson(['message' => 'Hanya Administrator yang dapat menghapus nomor antrean.']);
        $this->actingAs($administrator)->deleteJson("/api/antrean/tiket/{$ticket->id}")->assertNoContent();

        $this->assertModelMissing($ticket);
        $this->assertModelExists($kept);
        $this->assertSame(0, QueueEvent::query()->count());
        $activity = Activity::query()->sole();
        $this->assertSame(['menghapus', 'Nomor antrean B-005 (14 Okt 2026)', 'antrean', $administrator->id], [$activity->action, $activity->target, $activity->module->value, $activity->user_id]);
        $this->assertStringNotContainsString('DH', $activity->target);
    }

    public function test_keeps_running_tickets_and_today_s_numbers(): void
    {
        $running = QueueTicket::factory()->create(['nomor' => 'A-008', 'tanggal' => '2026-10-14', 'status' => 'ditunda']);
        $today = QueueTicket::factory()->cancelled()->create(['nomor' => 'A-009', 'tanggal' => '2026-10-15']);
        $this->actingAs(User::factory()->administrator()->create());

        $this->deleteJson("/api/antrean/tiket/{$running->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tiket' => 'Nomor A-008 masih berjalan. Batalkan dulu sebelum menghapusnya.']);
        $this->deleteJson("/api/antrean/tiket/{$today->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tiket' => 'Nomor A-009 dicetak hari ini dan baru bisa dihapus mulai besok, agar nomornya tidak terpakai dua kali.']);
        $this->assertSame(2, QueueTicket::query()->count());
        $this->assertSame(0, Activity::query()->count());
    }

    /**
     * @param  array<string, string>  $filters
     */
    private function search(array $filters = []): TestResponse
    {
        return $this->actingAs($this->employee)->getJson('/api/antrean/arsip?'.http_build_query($filters));
    }
}
