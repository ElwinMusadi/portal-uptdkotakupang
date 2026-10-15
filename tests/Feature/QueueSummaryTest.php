<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\QueueTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class QueueSummaryTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const array STAGES = ['pendaftaran', 'penetapan', 'kwitansi'];

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-15 03:00:00');
        $this->employee = User::factory()->withModules(DashboardModule::Antrean)->create();
    }

    public function test_counts_tickets_per_service_and_per_day_of_the_range(): void
    {
        QueueTicket::factory()->create(['tanggal' => '2026-10-13', 'status' => 'selesai', 'tahap' => null]);
        QueueTicket::factory()->cancelled()->create(['tanggal' => '2026-10-13']);
        QueueTicket::factory()->of('B')->create(['tanggal' => '2026-10-15']);
        QueueTicket::factory()->of('B')->create(['tanggal' => '2026-10-15', 'status' => 'selesai', 'tahap' => null]);
        QueueTicket::factory()->create(['tanggal' => '2026-10-12']);
        QueueTicket::factory()->create(['tanggal' => '2026-10-16']);

        $response = $this->summary(['dari' => '2026-10-13', 'sampai' => '2026-10-15'])->assertOk();

        $response->assertJsonPath('dari', '2026-10-13')
            ->assertJsonPath('sampai', '2026-10-15')
            ->assertJsonPath('jumlah', ['dicetak' => 4, 'selesai' => 2, 'batal' => 1, 'berjalan' => 1, 'ditunda' => 0])
            ->assertJsonPath('layanan', [
                ['kode' => 'A', 'nama' => 'Registrasi Ulang', 'dicetak' => 2, 'selesai' => 1, 'batal' => 1, 'berjalan' => 0],
                ['kode' => 'B', 'nama' => 'Pengesahan', 'dicetak' => 2, 'selesai' => 1, 'batal' => 0, 'berjalan' => 1],
                ['kode' => 'C', 'nama' => 'BBN/Mutasi', 'dicetak' => 0, 'selesai' => 0, 'batal' => 0, 'berjalan' => 0],
                ['kode' => 'D', 'nama' => 'Pembayaran Online', 'dicetak' => 0, 'selesai' => 0, 'batal' => 0, 'berjalan' => 0],
            ])
            ->assertJsonPath('harian', [
                ['tanggal' => '2026-10-13', 'dicetak' => 2, 'selesai' => 1, 'batal' => 1, 'berjalan' => 0],
                ['tanggal' => '2026-10-14', 'dicetak' => 0, 'selesai' => 0, 'batal' => 0, 'berjalan' => 0],
                ['tanggal' => '2026-10-15', 'dicetak' => 2, 'selesai' => 1, 'batal' => 0, 'berjalan' => 1],
            ]);
    }

    public function test_measures_each_stage_from_recorded_moments_only(): void
    {
        $this->ticketWith(['status' => 'selesai', 'tahap' => null], [
            ['dipanggil', 'pendaftaran', '01:10'], ['tahap selesai', 'pendaftaran', '01:20'],
            ['tahap selesai', 'penetapan', '01:50'],
            ['dipanggil', 'kwitansi', '02:00'], ['ditunda', 'kwitansi', '02:05'], ['dipanggil', 'kwitansi', '02:20'], ['tahap selesai', 'kwitansi', '02:30'],
            ['selesai', null, '02:30'],
        ]);
        // A station passed Pendaftaran on its own: Penetapan's start is unknown
        $this->ticketWith(['status' => 'selesai', 'tahap' => null], [
            ['tahap selesai', 'pendaftaran', '01:40', true], ['tahap selesai', 'penetapan', '01:40'],
            ['ditunda', 'kwitansi', '01:45', true], ['dipanggil', 'kwitansi', '02:00'], ['tahap selesai', 'kwitansi', '02:10'],
        ]);
        // Kwitansi marked, then undone: it is not done
        $this->ticketWith(['tahap' => 'kwitansi'], [
            ['dipanggil', 'pendaftaran', '01:30'], ['tahap selesai', 'pendaftaran', '01:40'], ['tahap selesai', 'penetapan', '02:00'],
            ['tahap selesai', 'kwitansi', '02:20'], ['diurungkan', 'kwitansi', '02:21'],
        ]);
        $this->ticketWith(['tahap' => 'kwitansi', 'status' => 'batal'], [
            ['tahap selesai', 'pendaftaran', '01:05'], ['tahap selesai', 'penetapan', '01:15'],
        ]);
        // Called straight from Pendaftaran by Loket Kwitansi: the wait is unknown
        $this->ticketWith(['tahap' => 'kwitansi', 'status' => 'dipanggil'], [
            ['tahap selesai', 'pendaftaran', '02:00', true], ['tahap selesai', 'penetapan', '02:00', true], ['dipanggil', 'kwitansi', '02:00'],
        ]);
        // Called at Kwitansi and still there: the wait counts
        $this->ticketWith(['tahap' => 'kwitansi', 'status' => 'dipanggil'], [
            ['tahap selesai', 'pendaftaran', '01:10'], ['tahap selesai', 'penetapan', '01:20'], ['dipanggil', 'kwitansi', '01:50'],
        ]);

        $response = $this->summary(['dari' => '2026-10-15'])->assertOk();

        $response->assertJsonPath('jumlah.ditunda', 1)
            ->assertJsonPath('tahap.0', ['kunci' => 'pendaftaran', 'nama' => 'Loket 1 – Pendaftaran', 'dipanggil' => true, 'selesai' => 4, 'otomatis' => 2, 'ditunda' => 0, 'tunggu' => 1200, 'lama' => 900])
            ->assertJsonPath('tahap.1', ['kunci' => 'penetapan', 'nama' => 'Penetapan', 'dipanggil' => false, 'selesai' => 5, 'otomatis' => 1, 'ditunda' => 0, 'tunggu' => null, 'lama' => 900])
            ->assertJsonPath('tahap.2', ['kunci' => 'kwitansi', 'nama' => 'Loket Kwitansi', 'dipanggil' => true, 'selesai' => 2, 'otomatis' => 0, 'ditunda' => 1, 'tunggu' => 1200, 'lama' => 2100])
            ->assertJsonPath('tahap.3', ['kunci' => 'pembayaran', 'nama' => 'Pembayaran', 'dipanggil' => false, 'selesai' => 0, 'otomatis' => 0, 'ditunda' => 0, 'tunggu' => null, 'lama' => null])
            ->assertJsonCount(7, 'tahap');
    }

    public function test_covers_today_by_default_and_at_most_31_days(): void
    {
        QueueTicket::factory()->create(['tanggal' => '2026-10-14']);
        QueueTicket::factory()->create(['tanggal' => '2026-10-15']);

        $this->summary()->assertOk()->assertJsonPath('dari', '2026-10-15')->assertJsonPath('sampai', '2026-10-15')->assertJsonPath('jumlah.dicetak', 1);
        $this->summary(['dari' => '2026-10-14'])->assertOk()->assertJsonPath('sampai', '2026-10-14')->assertJsonPath('jumlah.dicetak', 1);
        $this->summary(['dari' => '2026-10-01', 'sampai' => '2026-10-31'])->assertOk()->assertJsonCount(31, 'harian');
        $this->summary(['dari' => '2026-10-01', 'sampai' => '2026-11-01'])->assertUnprocessable()->assertJsonValidationErrors(['sampai' => 'Rentang ringkasan paling lama 31 hari.']);
        $this->summary(['dari' => '2026-10-15', 'sampai' => '2026-10-14'])->assertUnprocessable()->assertJsonValidationErrors(['sampai' => 'Tanggal akhir tidak boleh sebelum tanggal awal.']);
        $this->summary(['sampai' => '2026-10-14'])->assertUnprocessable()->assertJsonValidationErrors(['dari' => 'Pilih tanggal awal.']);
        $this->summary(['dari' => '2026/10/14'])->assertUnprocessable()->assertJsonValidationErrors(['dari' => 'Tanggal awal tidak valid.']);
    }

    public function test_lists_the_tickets_of_the_days_in_print_order_for_the_csv(): void
    {
        $later = QueueTicket::factory()->create(['tanggal' => '2026-10-15', 'created_at' => '2026-10-15 02:00:00']);
        $earlier = QueueTicket::factory()->create(['tanggal' => '2026-10-15', 'created_at' => '2026-10-15 01:00:00']);
        $dayBefore = QueueTicket::factory()->create(['tanggal' => '2026-10-14', 'created_at' => '2026-10-14 05:00:00', 'nopol' => 'DH77X']);
        QueueTicket::factory()->create(['tanggal' => '2026-10-13']);

        $this->actingAs($this->employee)->getJson('/api/antrean/ringkasan/tiket?dari=2026-10-14&sampai=2026-10-15')
            ->assertOk()
            ->assertJsonPath('*.id', [$dayBefore->id, $earlier->id, $later->id])
            ->assertJsonPath('0.nopol', 'DH 77 X');
        $this->actingAs($this->employee)->getJson('/api/antrean/ringkasan/tiket?dari=2026-09-01&sampai=2026-10-15')->assertUnprocessable();
    }

    /**
     * A ticket of service A going through three stages, printed at 01:00
     * UTC, with its history (action, stage, time, automatic).
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<array{0: string, 1: ?string, 2: string, 3?: bool}>  $events
     */
    private function ticketWith(array $attributes, array $events): void
    {
        $ticket = QueueTicket::factory()->create(['tahap_layanan' => self::STAGES, 'created_at' => '2026-10-15 01:00:00', ...$attributes]);

        foreach ($events as $event) {
            $ticket->events()->forceCreate([
                'queue_ticket_id' => $ticket->id, 'aksi' => $event[0], 'tahap' => $event[1], 'otomatis' => $event[3] ?? false, 'created_at' => "2026-10-15 {$event[2]}:00",
            ]);
        }
    }

    /**
     * @param  array<string, string>  $period
     */
    private function summary(array $period = []): TestResponse
    {
        return $this->actingAs($this->employee)->getJson('/api/antrean/ringkasan?'.http_build_query($period));
    }
}
