<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\RevenueImport;
use App\Models\RevenueTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class RevenueDetailsControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-20 10:00', 'Asia/Makassar'));
    }

    public function test_lists_the_day_s_transactions_counter_by_counter_in_file_order_with_their_details(): void
    {
        $office = RevenueImport::factory()->create();
        $unit = RevenueImport::factory()->counter('keliling-01', 'Samsat Keliling 01')->create();
        $second = RevenueTransaction::factory()->for($unit)->withDetails(['nopol' => 'DH2002BB', 'nama' => 'PEMILIK KELILING'])->create([
            'loket' => 'keliling-01', 'tanggal' => '2026-10-18',
        ]);
        $first = RevenueTransaction::factory()->for($office)->withDetails()->paying([
            'pkb' => 1200000, 'denda_pkb' => 24000, 'tunggakan' => 1100000, 'opsen_pkb' => 792000, 'opsen_denda_pkb' => 15840, 'opsen_tunggakan' => 726000,
        ])->create(['tanggal' => '2026-10-18', 'nomoratur' => '0012345', 'tahun_tunggakan' => 2]);
        $old = RevenueTransaction::factory()->for($office)->create(['tanggal' => '2026-10-18', 'nomoratur' => null]);
        RevenueTransaction::factory()->for($office)->withDetails()->create(['tanggal' => '2026-10-17']);

        $response = $this->details('tanggal=2026-10-18');

        $response->assertOk()
            ->assertJsonPath('tanggal', '2026-10-18')
            ->assertJsonPath('belumTercatat', 1)
            ->assertJsonCount(3, 'transaksi')
            ->assertJsonPath('transaksi.0', [
                'id' => (string) $first->id, 'loket' => 'kantor', 'namaLoket' => 'Loket SAMSAT', 'tanggal' => '2026-10-18',
                'jenisDaftar' => 'Teliti Ulang / Pengesahan', 'nomoratur' => '0012345', 'jnsKend' => 'SEPEDA MOTOR', 'merk' => 'HONDA',
                'warnaPlat' => 'PUTIH', 'uptAsal' => 'Kota Kupang', 'kelurahan' => 'Oebobo', 'kecamatan' => 'OEBOBO', 'tahunTunggakan' => 2,
                'pkb' => 1200000, 'bbn' => 0, 'dendaPkb' => 24000, 'dendaBbn' => 0, 'tunggakan' => 1100000, 'dendaTunggakan' => 0,
                'opsenPkb' => 792000, 'opsenBbn' => 0, 'opsenDendaPkb' => 15840, 'opsenDendaBbn' => 0, 'opsenTunggakan' => 726000,
                'opsenDendaTunggakan' => 0, 'jumlah' => 3857840,
                'nopol' => 'DH1234XY', 'nama' => 'PEMILIK RAHASIA', 'tipeKendaraan' => 'TIPE UJI 125', 'jatuhTempoAwal' => '2027-10-14',
                'jatuhTempoAkhir' => '2027-10-14', 'nilaiJual' => '18000000.00', 'uptBayar' => 'Kota Kupang', 'kodeJenisKendaraan' => 'J1',
                'jenisKendaraan' => 'SEPEDA MOTOR PRIBADI', 'rt' => '007', 'rw' => '003', 'ubahBentuk' => null,
            ])
            ->assertJsonPath('transaksi.1.id', (string) $old->id)
            ->assertJsonPath('transaksi.1.nomoratur', null)
            ->assertJsonPath('transaksi.1.nopol', null)
            ->assertJsonPath('transaksi.1.nama', null)
            ->assertJsonPath('transaksi.1.rt', null)
            ->assertJsonPath('transaksi.2.id', (string) $second->id)
            ->assertJsonPath('transaksi.2.namaLoket', 'Samsat Keliling 01')
            ->assertJsonPath('transaksi.2.nama', 'PEMILIK KELILING');
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
    }

    public function test_narrows_the_day_like_the_report(): void
    {
        $office = RevenueImport::factory()->create();
        RevenueTransaction::factory()->for($office)->withDetails(['nama' => 'PEMILIK BARU'])->create(['tanggal' => '2026-10-18', 'jenis_daftar' => 'BARU']);
        RevenueTransaction::factory()->for($office)->withDetails(['nama' => 'PEMILIK MENUNGGAK'])->create(['tanggal' => '2026-10-18', 'tahun_tunggakan' => 1]);
        RevenueTransaction::factory()->for($office)->withDetails(['nama' => 'PEMILIK OESAPA'])->create(['tanggal' => '2026-10-18', 'kelurahan' => 'Oesapa']);

        $this->assertSame(['PEMILIK BARU'], $this->details('tanggal=2026-10-18&jenisDaftar[]=BARU')->json('transaksi.*.nama'));
        $this->assertSame(['PEMILIK MENUNGGAK'], $this->details('tanggal=2026-10-18&tunggakan=ada')->json('transaksi.*.nama'));
        $this->assertSame(['PEMILIK OESAPA'], $this->details('tanggal=2026-10-18&kelurahan[]=Oesapa')->json('transaksi.*.nama'));
        $this->details('tanggal=2026-10-18&kelurahan[]=Lasiana')->assertOk()->assertExactJson(['tanggal' => '2026-10-18', 'transaksi' => [], 'belumTercatat' => 0]);
    }

    #[TestWith(['2026-10-14'])]
    #[TestWith(['2026-10-20'])]
    public function test_accepts_each_of_the_last_7_days(string $day): void
    {
        $this->details("tanggal={$day}")->assertOk()->assertJsonPath('tanggal', $day);
    }

    public function test_counts_the_last_7_days_in_kupang_time(): void
    {
        // 16:30 UTC on 20 October is already 21 October in Kupang
        $this->travelTo(Carbon::parse('2026-10-20 16:30', 'UTC'));

        $this->details('tanggal=2026-10-21')->assertOk();
        $this->details('tanggal=2026-10-14')->assertUnprocessable()->assertOnlyJsonValidationErrors(['tanggal' => 'Rincian per transaksi hanya tersedia untuk 7 hari terakhir.']);
    }

    #[TestWith(['tanggal=2026-10-13', 'tanggal', 'Rincian per transaksi hanya tersedia untuk 7 hari terakhir.'])]
    #[TestWith(['tanggal=2026-10-21', 'tanggal', 'Rincian per transaksi hanya tersedia untuk 7 hari terakhir.'])]
    #[TestWith(['tanggal=18-10-2026', 'tanggal', 'Tanggal rincian tidak valid.'])]
    #[TestWith(['', 'tanggal', 'Pilih tanggal rincian.'])]
    #[TestWith(['tanggal=2026-10-18&tunggakan=semua', 'tunggakan', 'Saringan tunggakan tidak dikenal.'])]
    #[TestWith(['tanggal=2026-10-18&loket=kantor', 'loket', 'Saringan loket tidak valid.'])]
    public function test_rejects_a_day_outside_the_last_7_or_an_invalid_filter(string $query, string $field, string $message): void
    {
        $this->details($query)->assertUnprocessable()->assertOnlyJsonValidationErrors([$field => $message]);
    }

    public function test_lets_administrators_see_the_details_too(): void
    {
        RevenueTransaction::factory()->withDetails()->create(['tanggal' => '2026-10-18']);

        $this->actingAs(User::factory()->administrator()->create())
            ->getJson('/api/penerimaan/rincian?tanggal=2026-10-18')
            ->assertOk()
            ->assertJsonPath('transaksi.0.nama', 'PEMILIK RAHASIA');
    }

    private function details(string $query): TestResponse
    {
        return $this->actingAs(User::factory()->withModules(DashboardModule::Penerimaan)->create())
            ->getJson("/api/penerimaan/rincian?{$query}");
    }
}
