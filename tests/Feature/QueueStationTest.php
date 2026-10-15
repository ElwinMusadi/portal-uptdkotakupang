<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Enums\QueueStage;
use App\Models\QueueCounter;
use App\Models\QueueEvent;
use App\Models\QueueTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class QueueStationTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-15 03:00:00');
        $this->employee = User::factory()->withModules(DashboardModule::Antrean)->create(['name' => 'Petugas Penetapan']);
    }

    public function test_returns_401_to_guests_and_403_to_employees_without_the_module(): void
    {
        $ticket = QueueTicket::factory()->waitingAt(QueueStage::Penetapan)->create();
        $requests = [
            ['GET', '/api/antrean/pos/penetapan'],
            ['POST', '/api/antrean/pos/penetapan/tandai'],
            ['POST', "/api/antrean/tiket/{$ticket->id}/perubahan-data"],
            ['POST', "/api/antrean/tiket/{$ticket->id}/urungkan"],
        ];

        foreach ($requests as [$method, $uri]) {
            $this->json($method, $uri, ['tiket' => $ticket->id])->assertUnauthorized();
        }
        $this->actingAs(User::factory()->withModules(DashboardModule::Papan)->create());
        foreach ($requests as [$method, $uri]) {
            $this->json($method, $uri, ['tiket' => $ticket->id])->assertForbidden()->assertExactJson(['message' => 'Modul Antrean Online belum ditugaskan kepada Anda.']);
        }
        $this->assertSame('penetapan', $ticket->fresh()->tahap->value);
    }

    public function test_knows_only_the_stations_that_mark_stages(): void
    {
        $this->actingAs($this->employee);

        foreach (['kwitansi', 'pembayaran', 'pendaftaran', 'Penetapan'] as $station) {
            $this->getJson("/api/antrean/pos/{$station}")->assertNotFound();
        }
    }

    public function test_lists_the_numbers_at_each_station_longest_waiting_first(): void
    {
        $later = QueueTicket::factory()->waitingAt(QueueStage::Penetapan, '2026-10-15 02:00:00')->create();
        $earlier = QueueTicket::factory()->waitingAt(QueueStage::Penetapan, '2026-10-14 06:00:00')->create(['tanggal' => '2026-10-14']);
        $dataChange = QueueTicket::factory()->waitingAt(QueueStage::Penetapan, '2026-10-15 01:00:00')->create(['status' => 'perubahan-data']);
        QueueTicket::factory()->waitingAt(QueueStage::Penetapan)->cancelled()->create();
        QueueTicket::factory()->waitingAt(QueueStage::Pendaftaran)->create();
        $payment = QueueTicket::factory()->waitingAt(QueueStage::Pembayaran)->create();
        $notice = QueueTicket::factory()->waitingAt(QueueStage::CetakSkpd)->create();
        $this->actingAs($this->employee);

        $this->getJson('/api/antrean/pos/penetapan')->assertOk()->assertJsonPath('*.id', [$earlier->id, $dataChange->id, $later->id]);
        $this->getJson('/api/antrean/pos/server')->assertOk()->assertJsonPath('*.id', [$dataChange->id])->assertJsonPath('0.status', 'perubahan-data');
        $this->getJson('/api/antrean/pos/pelunasan')->assertOk()->assertJsonPath('*.id', [$payment->id]);
        $this->getJson('/api/antrean/pos/cetak-skpd')->assertOk()->assertJsonPath('*.id', [$notice->id]);
        $this->getJson('/api/antrean/pos/cetak-stnk')->assertOk()->assertExactJson([]);
    }

    public function test_marks_the_stage_done_and_keeps_the_ticket_s_place_for_undoing(): void
    {
        $ticket = QueueTicket::factory()->waitingAt(QueueStage::Penetapan, '2026-10-15 02:00:00')->create();

        $this->mark('penetapan', $ticket)
            ->assertOk()
            ->assertJsonPath('id', $ticket->id)
            ->assertJsonPath('tahap', 'kwitansi')
            ->assertJsonPath('status', 'menunggu')
            ->assertJsonPath('masukTahap', '2026-10-15T03:00:00+00:00');

        $event = QueueEvent::query()->where('aksi', 'tahap selesai')->sole();
        $this->assertSame([QueueStage::Penetapan, false, $this->employee->id, null], [$event->tahap, $event->otomatis, $event->user_id, $event->queue_counter_id]);
        $this->assertSame(['penetapan', 'menunggu', '2026-10-15 02:00:00'], [$event->keadaan['sebelum']['tahap'], $event->keadaan['sebelum']['status'], $event->keadaan['sebelum']['masuk_tahap_pada']]);
        $this->assertSame($ticket->fresh()->snapshot(), $event->keadaan['sesudah']);
    }

    public function test_passes_the_earlier_stages_even_when_called_or_put_aside_at_one(): void
    {
        $receipt = QueueCounter::query()->where('tahap', 'kwitansi')->sole();
        $called = QueueTicket::factory()->of('C')->calledTo($receipt, times: 2)->create();
        $aside = QueueTicket::factory()->waitingAt(QueueStage::Pendaftaran)->create(['status' => 'ditunda']);

        $this->mark('pelunasan', $called)->assertOk()->assertJsonPath('tahap', 'cetak-skpd')->assertJsonPath('status', 'menunggu')->assertJsonPath('loket', null);
        $this->mark('cetak-stnk', $aside)->assertOk()->assertJsonPath('tahap', 'penyerahan');

        $this->assertSame([null, null, 0], [$called->fresh()->queue_counter_id, $called->fresh()->dipanggil_pada, $called->fresh()->jumlah_panggil]);
        $this->assertSame(
            [['kwitansi', true], ['pembayaran', false]],
            $called->events()->where('aksi', 'tahap selesai')->get()->map(fn (QueueEvent $event): array => [$event->tahap->value, $event->otomatis])->all(),
        );
        $this->assertSame(
            [['pendaftaran', true], ['penetapan', true], ['kwitansi', true], ['pembayaran', true], ['cetak-skpd', true], ['cetak-stnk', false]],
            $aside->events()->where('aksi', 'tahap selesai')->get()->map(fn (QueueEvent $event): array => [$event->tahap->value, $event->otomatis])->all(),
        );
        $this->assertSame(1, QueueEvent::query()->whereNotNull('keadaan')->whereBelongsTo($aside, 'ticket')->count());
    }

    public function test_finishes_a_ticket_whose_last_stage_is_marked(): void
    {
        $ticket = QueueTicket::factory()->waitingAt(QueueStage::CetakStnk)->create(['tahap_layanan' => ['pendaftaran', 'cetak-stnk']]);

        $this->mark('cetak-stnk', $ticket)->assertOk()->assertJsonPath('status', 'selesai')->assertJsonPath('tahap', null)->assertJsonPath('selesai', '2026-10-15T03:00:00+00:00');

        $this->assertSame(['tahap selesai', 'selesai'], $ticket->events()->pluck('aksi')->all());
    }

    public function test_refuses_numbers_that_cannot_reach_the_station(): void
    {
        $cases = [
            [QueueTicket::factory()->waitingAt(QueueStage::Penetapan)->cancelled()->create(['nomor' => 'A-001']), 'penetapan', 'Nomor A-001 sudah dibatalkan.'],
            [QueueTicket::factory()->create(['nomor' => 'A-002', 'tahap' => null, 'status' => 'selesai']), 'cetak-skpd', 'Nomor A-002 sudah selesai.'],
            [QueueTicket::factory()->of('C')->waitingAt(QueueStage::Penetapan)->create(['nomor' => 'C-003']), 'cetak-stnk', 'Layanan nomor C-003 tidak melewati Cetak STNK.'],
            [QueueTicket::factory()->waitingAt(QueueStage::Kwitansi)->create(['nomor' => 'A-004']), 'penetapan', 'Nomor A-004 sudah melewati Penetapan.'],
            [QueueTicket::factory()->waitingAt(QueueStage::Penetapan)->create(['nomor' => 'A-005', 'status' => 'perubahan-data']), 'penetapan', 'Nomor A-005 sedang menunggu perubahan data di Server.'],
            [QueueTicket::factory()->waitingAt(QueueStage::Penetapan)->create(['nomor' => 'A-006', 'status' => 'perubahan-data']), 'pelunasan', 'Nomor A-006 sedang menunggu perubahan data di Server.'],
        ];

        foreach ($cases as [$ticket, $station, $message]) {
            $before = $ticket->snapshot();
            $this->mark($station, $ticket)->assertUnprocessable()->assertJsonValidationErrors(['tiket' => $message]);
            $this->assertSame($before, $ticket->fresh()->snapshot());
        }
        $this->assertSame(0, QueueEvent::query()->count());
    }

    public function test_requires_an_existing_number(): void
    {
        $this->actingAs($this->employee);

        $this->postJson('/api/antrean/pos/penetapan/tandai')->assertUnprocessable()->assertJsonValidationErrors(['tiket' => 'Pilih nomor antrean yang ditandai.']);
        $this->postJson('/api/antrean/pos/penetapan/tandai', ['tiket' => '01K7M00000000000000000000M'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tiket' => 'Nomor antrean ini tidak ditemukan.']);
        $this->postJson('/api/antrean/pos/penetapan/tandai', ['tiket' => ['x']])->assertUnprocessable()->assertJsonValidationErrors(['tiket' => 'Nomor antrean ini tidak ditemukan.']);
    }

    public function test_sends_documents_to_server_for_a_data_change_and_back_to_their_old_place(): void
    {
        $ticket = QueueTicket::factory()->waitingAt(QueueStage::Pendaftaran)->create(['nomor' => 'A-007']);
        $this->actingAs($this->employee);

        $this->postJson("/api/antrean/tiket/{$ticket->id}/perubahan-data")->assertOk()->assertJsonPath('tahap', 'penetapan')->assertJsonPath('status', 'perubahan-data');
        $this->postJson("/api/antrean/tiket/{$ticket->id}/perubahan-data")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tiket' => 'Nomor A-007 sedang menunggu perubahan data di Server.']);
        $this->travel(20)->minutes();
        $this->mark('server', $ticket)->assertOk()->assertJsonPath('tahap', 'penetapan')->assertJsonPath('status', 'menunggu')->assertJsonPath('masukTahap', '2026-10-15T03:00:00+00:00');
        $this->mark('server', $ticket)->assertUnprocessable()->assertJsonValidationErrors(['tiket' => 'Nomor A-007 tidak sedang menunggu perubahan data.']);

        $this->assertSame(
            [['tahap selesai', 'pendaftaran', true, false], ['perlu perubahan data', 'penetapan', false, true], ['perubahan data selesai', 'penetapan', false, true]],
            $ticket->events()->get()->map(fn (QueueEvent $event): array => [$event->aksi, $event->tahap->value, $event->otomatis, $event->keadaan !== null])->all(),
        );
    }

    public function test_refuses_a_data_change_for_numbers_past_or_without_penetapan(): void
    {
        $past = QueueTicket::factory()->waitingAt(QueueStage::Kwitansi)->create(['nomor' => 'A-008']);
        $without = QueueTicket::factory()->create(['nomor' => 'A-009', 'tahap_layanan' => ['pendaftaran', 'penyerahan']]);
        $this->actingAs($this->employee);

        $this->postJson("/api/antrean/tiket/{$past->id}/perubahan-data")->assertUnprocessable()->assertJsonValidationErrors(['tiket' => 'Nomor A-008 sudah melewati Penetapan.']);
        $this->postJson("/api/antrean/tiket/{$without->id}/perubahan-data")->assertUnprocessable()->assertJsonValidationErrors(['tiket' => 'Layanan nomor A-009 tidak melewati Penetapan.']);
    }

    public function test_counters_cannot_call_a_number_out_for_a_data_change(): void
    {
        $receipt = QueueCounter::query()->where('tahap', 'kwitansi')->sole();
        $ticket = QueueTicket::factory()->waitingAt(QueueStage::Penetapan)->create(['nomor' => 'A-010', 'status' => 'perubahan-data']);

        $this->actingAs($this->employee)->postJson("/api/antrean/loket/{$receipt->id}/panggil", ['tiket' => $ticket->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tiket' => 'Nomor A-010 sedang menunggu perubahan data di Server.']);
    }

    public function test_undoes_a_marking_back_to_exactly_where_the_ticket_was(): void
    {
        $registration = QueueCounter::query()->where('tahap', 'pendaftaran')->sole();
        $ticket = QueueTicket::factory()->calledTo($registration, times: 2)->create(['nomor' => 'A-011', 'masuk_tahap_pada' => '2026-10-15 00:30:00']);
        $before = $ticket->fresh()->snapshot();
        $this->mark('penetapan', $ticket)->assertJsonPath('tahap', 'kwitansi');

        $this->undo($ticket)
            ->assertOk()
            ->assertJsonPath('tahap', 'pendaftaran')
            ->assertJsonPath('status', 'dipanggil')
            ->assertJsonPath('loket', 'Loket 1 – Pendaftaran')
            ->assertJsonPath('dipanggil', '2026-10-15T02:00:00+00:00')
            ->assertJsonPath('jumlahPanggil', 2)
            ->assertJsonPath('masukTahap', '2026-10-15T00:30:00+00:00');

        $this->assertSame($before, $ticket->fresh()->snapshot());
        $this->assertDatabaseHas('queue_events', ['queue_ticket_id' => $ticket->id, 'aksi' => 'diurungkan', 'tahap' => 'penetapan', 'keterangan' => 'tahap selesai', 'user_id' => $this->employee->id]);
        $this->undo($ticket)->assertUnprocessable()->assertJsonValidationErrors(['tiket' => 'Tidak ada tanda Anda pada nomor A-011 yang bisa diurungkan.']);
    }

    public function test_undoes_a_marking_whose_recorded_place_comes_back_in_another_key_order(): void
    {
        $ticket = QueueTicket::factory()->waitingAt(QueueStage::Penetapan)->create();
        $this->mark('penetapan', $ticket);
        $event = QueueEvent::query()->whereNotNull('keadaan')->sole();
        $event->forceFill(['keadaan' => array_map(fn (array $state): array => array_reverse($state, preserve_keys: true), $event->keadaan)])->save();

        $this->undo($ticket)->assertOk()->assertJsonPath('tahap', 'penetapan');
    }

    public function test_undoes_finishing_the_last_stage_and_data_changes(): void
    {
        $last = QueueTicket::factory()->waitingAt(QueueStage::CetakStnk)->create(['tahap_layanan' => ['pendaftaran', 'cetak-stnk']]);
        $change = QueueTicket::factory()->waitingAt(QueueStage::Penetapan)->create();
        $this->actingAs($this->employee);

        $this->mark('cetak-stnk', $last);
        $this->undo($last)->assertOk()->assertJsonPath('status', 'menunggu')->assertJsonPath('tahap', 'cetak-stnk')->assertJsonPath('selesai', null);
        $this->postJson("/api/antrean/tiket/{$change->id}/perubahan-data");
        $this->undo($change)->assertOk()->assertJsonPath('status', 'menunggu');
        $this->postJson("/api/antrean/tiket/{$change->id}/perubahan-data");
        $this->mark('server', $change);
        $this->undo($change)->assertOk()->assertJsonPath('status', 'perubahan-data');
        $this->assertDatabaseHas('queue_events', ['queue_ticket_id' => $change->id, 'aksi' => 'diurungkan', 'keterangan' => 'perubahan data selesai']);
    }

    public function test_undoes_only_the_employee_s_own_marking_while_the_ticket_has_not_moved_on(): void
    {
        $receipt = QueueCounter::query()->where('tahap', 'kwitansi')->sole();
        $marked = QueueTicket::factory()->waitingAt(QueueStage::Penetapan)->create(['nomor' => 'A-012']);
        $movedOn = QueueTicket::factory()->waitingAt(QueueStage::Penetapan)->create(['nomor' => 'A-013']);
        $atCounter = QueueTicket::factory()->calledTo($receipt)->create(['nomor' => 'A-014']);
        $this->mark('penetapan', $marked);
        $this->mark('penetapan', $movedOn);
        $this->actingAs($this->employee)->postJson("/api/antrean/tiket/{$atCounter->id}/selesai")->assertOk();
        $this->actingAs($this->employee)->postJson("/api/antrean/loket/{$receipt->id}/panggil", ['tiket' => $movedOn->id])->assertOk();

        $this->actingAs(User::factory()->withModules(DashboardModule::Antrean)->create());
        $this->postJson("/api/antrean/tiket/{$marked->id}/urungkan")->assertUnprocessable()->assertJsonValidationErrors(['tiket' => 'Tidak ada tanda Anda pada nomor A-012 yang bisa diurungkan.']);
        $this->undo($movedOn)->assertUnprocessable()->assertJsonValidationErrors(['tiket' => 'Nomor A-013 sudah berubah sesudah Anda menandainya, jadi tidak bisa diurungkan.']);
        $this->undo($atCounter)->assertUnprocessable()->assertJsonValidationErrors(['tiket' => 'Tidak ada tanda Anda pada nomor A-014 yang bisa diurungkan.']);
        $this->assertSame(['kwitansi', 'dipanggil'], [$movedOn->fresh()->tahap->value, $movedOn->fresh()->status->value]);
    }

    private function mark(string $station, QueueTicket $ticket): TestResponse
    {
        return $this->actingAs($this->employee)->postJson("/api/antrean/pos/{$station}/tandai", ['tiket' => $ticket->id]);
    }

    private function undo(QueueTicket $ticket): TestResponse
    {
        return $this->actingAs($this->employee)->postJson("/api/antrean/tiket/{$ticket->id}/urungkan");
    }
}
