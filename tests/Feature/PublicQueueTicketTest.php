<?php

namespace Tests\Feature;

use App\Enums\QueueStage;
use App\Models\QueueCounter;
use App\Models\QueueService;
use App\Models\QueueTicket;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PublicQueueTicketTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_shows_a_ticket_by_its_tracking_code_without_naming_staff(): void
    {
        $ticket = QueueTicket::factory()->create([
            'queue_service_id' => QueueService::query()->where('kode', 'C')->value('id'),
            'nomor' => 'C-004',
            'tahap_layanan' => ['pendaftaran', 'penetapan', 'kwitansi', 'pembayaran', 'cetak-skpd', 'penyerahan'],
            'created_at' => '2026-10-15 01:00:00',
        ]);

        $response = $this->track($ticket->kode_lacak);

        $response->assertOk()->assertExactJson([
            'nomor' => 'C-004',
            'tanggal' => '2026-10-15',
            'layanan' => ['kode' => 'C', 'nama' => 'BBN/Mutasi'],
            'nopol' => 'DH 1234 AB',
            'status' => 'menunggu',
            'tahap' => 'pendaftaran',
            'petunjuk' => 'Tunggu nomor Anda dipanggil di Loket 1 – Pendaftaran.',
            'tahapan' => [
                ['kunci' => 'pendaftaran', 'nama' => 'Loket 1 – Pendaftaran', 'keadaan' => 'sekarang'],
                ['kunci' => 'penetapan', 'nama' => 'Penetapan', 'keadaan' => 'nanti'],
                ['kunci' => 'kwitansi', 'nama' => 'Loket Kwitansi', 'keadaan' => 'nanti'],
                ['kunci' => 'pembayaran', 'nama' => 'Pembayaran', 'keadaan' => 'nanti'],
                ['kunci' => 'cetak-skpd', 'nama' => 'Cetak SKPD', 'keadaan' => 'nanti'],
                ['kunci' => 'penyerahan', 'nama' => 'Loket 2 – Penyerahan', 'keadaan' => 'nanti'],
            ],
            'dicetak' => '2026-10-15T01:00:00+00:00',
        ]);
        $this->assertSame([], $response->headers->getCookies());
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_marks_the_stages_before_the_current_one_as_done(): void
    {
        $ticket = QueueTicket::factory()->create(['tahap' => QueueStage::Kwitansi]);

        $this->track($ticket->kode_lacak)
            ->assertJsonPath('petunjuk', 'Tunggu nomor Anda dipanggil di Loket Kwitansi.')
            ->assertJsonPath('tahapan.*.keadaan', ['selesai', 'selesai', 'sekarang', 'nanti', 'nanti', 'nanti', 'nanti']);
    }

    public function test_says_a_cancelled_ticket_is_cancelled_without_the_reason(): void
    {
        $ticket = QueueTicket::factory()->cancelled()->create(['tahap' => QueueStage::Penetapan, 'nopol' => null]);

        $response = $this->track($ticket->kode_lacak);

        $response->assertOk()
            ->assertJsonPath('status', 'batal')
            ->assertJsonPath('nopol', null)
            ->assertJsonPath('petunjuk', 'Nomor antrean ini dibatalkan petugas. Silakan tanyakan ke Meja Informasi.')
            ->assertJsonPath('tahapan.*.keadaan', ['selesai', 'nanti', 'nanti', 'nanti', 'nanti', 'nanti', 'nanti']);
        $this->assertStringNotContainsString('Berkas belum lengkap', $response->getContent());
    }

    public function test_tells_where_a_called_put_aside_or_finished_number_goes(): void
    {
        $receipt = QueueCounter::query()->where('tahap', 'kwitansi')->sole();
        $called = QueueTicket::factory()->calledTo($receipt)->create();
        $aside = QueueTicket::factory()->create(['tahap' => QueueStage::Kwitansi, 'status' => 'ditunda']);
        $done = QueueTicket::factory()->create(['tahap' => null, 'status' => 'selesai']);

        $this->track($called->kode_lacak)
            ->assertJsonPath('status', 'dipanggil')
            ->assertJsonPath('petunjuk', 'Nomor Anda dipanggil. Silakan menuju Loket Kwitansi.')
            ->assertJsonPath('tahapan.2.keadaan', 'sekarang');
        $this->track($aside->kode_lacak)
            ->assertJsonPath('petunjuk', 'Nomor Anda sudah dipanggil ke Loket Kwitansi, tetapi Anda belum datang. Silakan lapor ke petugas Loket Kwitansi.');
        $this->track($done->kode_lacak)
            ->assertJsonPath('status', 'selesai')
            ->assertJsonPath('petunjuk', 'Berkas Anda sudah selesai diproses. Terima kasih.')
            ->assertJsonPath('tahapan.*.keadaan', array_fill(0, 7, 'selesai'));
    }

    public function test_answers_404_for_an_unknown_code_and_422_for_query_parameters(): void
    {
        $ticket = QueueTicket::factory()->create();

        $this->track('AbCdEfGhIjKlMnOpQrStUv')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Nomor antrean tidak ditemukan. Periksa kembali QR pada tiket Anda.']);
        $this->getJson("/api/public/antrean/{$ticket->kode_lacak}?nopol=DH1234AB")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['query' => 'Parameter kueri tidak didukung.']);
        $this->getJson('/api/public/antrean/'.substr($ticket->kode_lacak, 1))->assertNotFound();
        $this->getJson('/api/public/antrean/DH1234AB')->assertNotFound();
    }

    private function track(string $code): TestResponse
    {
        return $this->getJson("/api/public/antrean/{$code}");
    }
}
