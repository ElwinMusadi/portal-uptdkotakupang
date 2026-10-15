<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Enums\QueueStage;
use App\Models\QueueCounter;
use App\Models\QueueService;
use App\Models\QueueTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class QueueCallTest extends TestCase
{
    use LazilyRefreshDatabase;

    private QueueCounter $registration;

    private QueueCounter $receipt;

    private QueueCounter $handover;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-15 03:00:00');
        $this->registration = QueueCounter::query()->where('tahap', 'pendaftaran')->sole();
        $this->receipt = QueueCounter::query()->where('tahap', 'kwitansi')->sole();
        $this->handover = QueueCounter::query()->where('tahap', 'penyerahan')->sole();
        $this->employee = User::factory()->withModules(DashboardModule::Antrean)->create(['name' => 'Petugas Loket']);
    }

    public function test_returns_401_to_guests_and_403_to_employees_without_the_module(): void
    {
        $ticket = QueueTicket::factory()->calledTo($this->registration)->create();
        $requests = [
            ['GET', "/api/antrean/loket/{$this->registration->id}/antrean"],
            ['POST', "/api/antrean/loket/{$this->registration->id}/panggil"],
            ['POST', "/api/antrean/tiket/{$ticket->id}/panggil-ulang"],
            ['POST', "/api/antrean/tiket/{$ticket->id}/tunda"],
            ['POST', "/api/antrean/tiket/{$ticket->id}/selesai"],
            ['GET', '/api/antrean/cari?q=A-001'],
        ];

        foreach ($requests as [$method, $uri]) {
            $this->json($method, $uri)->assertUnauthorized();
        }
        $this->actingAs(User::factory()->withModules(DashboardModule::Papan)->create());
        foreach ($requests as [$method, $uri]) {
            $this->json($method, $uri)->assertForbidden()->assertExactJson(['message' => 'Modul Antrean Online belum ditugaskan kepada Anda.']);
        }
    }

    public function test_calls_the_earliest_number_waiting_at_the_counter_s_stage_whatever_its_day(): void
    {
        $today = QueueTicket::factory()->waitingAt(QueueStage::Pendaftaran, '2026-10-15 01:00:00')->create(['nomor' => 'A-001']);
        $yesterday = QueueTicket::factory()->waitingAt(QueueStage::Pendaftaran, '2026-10-14 06:00:00')->create(['nomor' => 'A-009', 'tanggal' => '2026-10-14']);
        QueueTicket::factory()->waitingAt(QueueStage::Kwitansi, '2026-10-14 01:00:00')->create();

        $this->actingAs($this->employee)->postJson("/api/antrean/loket/{$this->registration->id}/panggil")
            ->assertOk()
            ->assertJsonPath('id', $yesterday->id)
            ->assertJsonPath('status', 'dipanggil')
            ->assertJsonPath('loket', 'Loket 1 – Pendaftaran')
            ->assertJsonPath('jumlahPanggil', 1)
            ->assertJsonPath('dipanggil', '2026-10-15T03:00:00+00:00');
        $this->assertDatabaseHas('queue_events', [
            'queue_ticket_id' => $yesterday->id, 'aksi' => 'dipanggil', 'tahap' => 'pendaftaran', 'queue_counter_id' => $this->registration->id, 'otomatis' => false, 'user_id' => $this->employee->id,
        ]);
        $this->assertSame('menunggu', $today->fresh()->status->value);
    }

    public function test_puts_aside_the_number_still_called_when_calling_the_next(): void
    {
        $called = QueueTicket::factory()->calledTo($this->registration)->create();
        $next = QueueTicket::factory()->waitingAt(QueueStage::Pendaftaran)->create();

        $this->actingAs($this->employee)->postJson("/api/antrean/loket/{$this->registration->id}/panggil")->assertJsonPath('id', $next->id);

        $this->assertSame(['ditunda', null], [$called->fresh()->status->value, $called->fresh()->queue_counter_id]);
        $this->assertDatabaseHas('queue_events', ['queue_ticket_id' => $called->id, 'aksi' => 'ditunda', 'queue_counter_id' => $this->registration->id, 'otomatis' => true]);
    }

    public function test_takes_only_the_services_the_counter_serves_and_keeps_the_called_number_when_none_waits(): void
    {
        $counter = QueueCounter::factory()->serving(['B'])->create(['nama' => 'Loket 1B']);
        QueueTicket::factory()->waitingAt(QueueStage::Pendaftaran, '2026-10-15 00:30:00')->create();
        $serviceB = QueueTicket::factory()->of('B')->waitingAt(QueueStage::Pendaftaran)->create();

        $this->actingAs($this->employee)->postJson("/api/antrean/loket/{$counter->id}/panggil")->assertJsonPath('id', $serviceB->id);
        $this->actingAs($this->employee)->postJson("/api/antrean/loket/{$counter->id}/panggil")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['loket' => 'Tidak ada nomor yang menunggu di Loket 1B.']);
        $this->assertSame('dipanggil', $serviceB->fresh()->status->value);
    }

    public function test_refuses_a_counter_not_in_use(): void
    {
        $this->registration->update(['aktif' => false]);
        QueueTicket::factory()->waitingAt(QueueStage::Pendaftaran)->create();

        $this->actingAs($this->employee)->postJson("/api/antrean/loket/{$this->registration->id}/panggil")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['loket' => 'Loket 1 – Pendaftaran sedang tidak dipakai.']);
    }

    public function test_calls_a_given_number_and_passes_the_stages_before_the_counter(): void
    {
        $ticket = QueueTicket::factory()->of('C')->waitingAt(QueueStage::Pendaftaran)->create();

        $this->actingAs($this->employee)->postJson("/api/antrean/loket/{$this->receipt->id}/panggil", ['tiket' => $ticket->id])
            ->assertOk()
            ->assertJsonPath('tahap', 'kwitansi')
            ->assertJsonPath('status', 'dipanggil')
            ->assertJsonPath('loket', 'Loket Kwitansi');

        $this->assertSame(
            [['tahap selesai', 'pendaftaran', true], ['tahap selesai', 'penetapan', true], ['dipanggil', 'kwitansi', false]],
            $ticket->events()->get()->map(fn ($event): array => [$event->aksi, $event->tahap?->value, $event->otomatis])->all(),
        );
    }

    public function test_puts_aside_the_number_still_called_when_calling_a_given_one(): void
    {
        $called = QueueTicket::factory()->calledTo($this->receipt)->create();
        $given = QueueTicket::factory()->waitingAt(QueueStage::Kwitansi)->create();

        $this->actingAs($this->employee)->postJson("/api/antrean/loket/{$this->receipt->id}/panggil", ['tiket' => $given->id])->assertJsonPath('id', $given->id);

        $this->assertSame('ditunda', $called->fresh()->status->value);
    }

    public function test_calling_the_number_a_counter_calls_calls_it_again(): void
    {
        $ticket = QueueTicket::factory()->calledTo($this->receipt)->create();

        $this->actingAs($this->employee)->postJson("/api/antrean/loket/{$this->receipt->id}/panggil", ['tiket' => $ticket->id])
            ->assertOk()
            ->assertJsonPath('jumlahPanggil', 2);
        $this->assertDatabaseHas('queue_events', ['queue_ticket_id' => $ticket->id, 'aksi' => 'dipanggil ulang']);
    }

    public function test_refuses_to_call_a_number_the_counter_cannot_take(): void
    {
        QueueService::query()->where('kode', 'D')->update(['tahap' => json_encode(['penetapan', 'penyerahan'])]);
        $cases = [
            [QueueTicket::factory()->cancelled()->create(['nomor' => 'A-001']), $this->registration, 'Nomor A-001 sudah dibatalkan.'],
            [QueueTicket::factory()->create(['nomor' => 'A-002', 'status' => 'selesai', 'tahap' => null]), $this->registration, 'Nomor A-002 sudah selesai.'],
            [QueueTicket::factory()->of('D')->waitingAt(QueueStage::Penetapan)->create(['nomor' => 'D-001']), $this->receipt, 'Layanan nomor D-001 tidak melewati Loket Kwitansi.'],
            [QueueTicket::factory()->waitingAt(QueueStage::Penyerahan)->create(['nomor' => 'A-003']), $this->registration, 'Nomor A-003 sudah melewati Loket 1 – Pendaftaran.'],
            [QueueTicket::factory()->calledTo($this->registration)->create(['nomor' => 'A-004']), $this->receipt, 'Nomor A-004 sedang dipanggil di Loket 1 – Pendaftaran.'],
        ];

        foreach ($cases as [$ticket, $counter, $message]) {
            $this->actingAs($this->employee)->postJson("/api/antrean/loket/{$counter->id}/panggil", ['tiket' => $ticket->id])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['tiket' => $message]);
        }
        $this->actingAs($this->employee)->postJson("/api/antrean/loket/{$this->registration->id}/panggil", ['tiket' => '01J0000000000000000000000X'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tiket' => 'Nomor antrean ini tidak ditemukan.']);
    }

    public function test_recalls_puts_aside_and_finishes_the_called_number(): void
    {
        $ticket = QueueTicket::factory()->calledTo($this->registration)->create(['nomor' => 'A-001']);

        $this->actingAs($this->employee)->postJson("/api/antrean/tiket/{$ticket->id}/panggil-ulang")->assertOk()->assertJsonPath('jumlahPanggil', 2);
        $this->actingAs($this->employee)->postJson("/api/antrean/tiket/{$ticket->id}/tunda")
            ->assertOk()
            ->assertJsonPath('status', 'ditunda')
            ->assertJsonPath('loket', null);
        $this->assertDatabaseHas('queue_events', ['queue_ticket_id' => $ticket->id, 'aksi' => 'ditunda', 'otomatis' => false]);
        foreach (['panggil-ulang', 'tunda', 'selesai'] as $action) {
            $this->actingAs($this->employee)->postJson("/api/antrean/tiket/{$ticket->id}/{$action}")
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['tiket' => 'Nomor A-001 sedang tidak dipanggil.']);
        }

        $this->actingAs($this->employee)->postJson("/api/antrean/loket/{$this->registration->id}/panggil", ['tiket' => $ticket->id])->assertJsonPath('jumlahPanggil', 3);
        $this->travelTo('2026-10-15 03:10:00');
        $this->actingAs($this->employee)->postJson("/api/antrean/tiket/{$ticket->id}/selesai")
            ->assertOk()
            ->assertJsonPath('tahap', 'penetapan')
            ->assertJsonPath('status', 'menunggu')
            ->assertJsonPath('loket', null)
            ->assertJsonPath('jumlahPanggil', 0)
            ->assertJsonPath('masukTahap', '2026-10-15T03:10:00+00:00');
        $this->assertDatabaseHas('queue_events', ['queue_ticket_id' => $ticket->id, 'aksi' => 'tahap selesai', 'tahap' => 'pendaftaran', 'queue_counter_id' => $this->registration->id, 'otomatis' => false]);
    }

    public function test_finishes_a_number_after_its_last_stage(): void
    {
        $ticket = QueueTicket::factory()->calledTo($this->handover)->create();

        $this->actingAs($this->employee)->postJson("/api/antrean/tiket/{$ticket->id}/selesai")
            ->assertOk()
            ->assertJsonPath('status', 'selesai')
            ->assertJsonPath('tahap', null)
            ->assertJsonPath('selesai', '2026-10-15T03:00:00+00:00');
        $this->assertSame(['tahap selesai', 'selesai'], $ticket->events()->pluck('aksi')->all());
        $this->actingAs($this->employee)->postJson("/api/antrean/tiket/{$ticket->id}/batal", ['alasan' => 'Salah.'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tiket' => "Nomor {$ticket->nomor} sudah selesai."]);
    }

    public function test_shows_a_counter_s_called_waiting_and_put_aside_numbers(): void
    {
        $counter = QueueCounter::factory()->serving(['A'])->create();
        $called = QueueTicket::factory()->calledTo($counter)->create();
        $later = QueueTicket::factory()->waitingAt(QueueStage::Pendaftaran, '2026-10-15 02:00:00')->create();
        $earlier = QueueTicket::factory()->waitingAt(QueueStage::Pendaftaran, '2026-10-14 02:00:00')->create(['tanggal' => '2026-10-14']);
        QueueTicket::factory()->of('B')->waitingAt(QueueStage::Pendaftaran)->create();
        QueueTicket::factory()->waitingAt(QueueStage::Kwitansi)->create();
        $aside = QueueTicket::factory()->create(['status' => 'ditunda', 'dipanggil_pada' => '2026-10-15 01:30:00']);

        $this->actingAs($this->employee)->getJson("/api/antrean/loket/{$counter->id}/antrean")
            ->assertOk()
            ->assertJsonPath('loket.id', $counter->id)
            ->assertJsonPath('dipanggil.id', $called->id)
            ->assertJsonPath('menunggu.*.id', [$earlier->id, $later->id])
            ->assertJsonPath('ditunda.*.id', [$aside->id]);
    }

    public function test_finds_open_numbers_by_qr_number_or_plate(): void
    {
        $yesterday = QueueTicket::factory()->create(['nomor' => 'A-001', 'tanggal' => '2026-10-14', 'nopol' => 'DH9AB']);
        $today = QueueTicket::factory()->create(['nomor' => 'A-001', 'nopol' => 'DH1234AB']);
        QueueTicket::factory()->cancelled()->create(['nomor' => 'A-001', 'nopol' => 'DH1234AB']);
        $this->actingAs($this->employee);

        $this->getJson('/api/antrean/cari?q='.urlencode($today->trackingUrl()))->assertOk()->assertJsonPath('*.id', [$today->id]);
        $this->getJson("/api/antrean/cari?q={$yesterday->kode_lacak}")->assertJsonPath('*.id', [$yesterday->id]);
        $this->getJson('/api/antrean/cari?q=a1')->assertJsonPath('*.id', [$today->id, $yesterday->id]);
        $this->getJson('/api/antrean/cari?q='.urlencode('dh 1234-ab'))->assertJsonPath('*.id', [$today->id]);
        $this->getJson('/api/antrean/cari?q=B-001')->assertOk()->assertExactJson([]);
        $this->getJson('/api/antrean/cari')->assertUnprocessable()->assertJsonValidationErrors(['q' => 'Pindai QR atau ketik nomor antrean atau nomor polisi.']);
    }
}
