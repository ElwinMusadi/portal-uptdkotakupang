<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\MobileServiceStop;
use App\Models\RevenueImport;
use App\Models\RevenueTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class RevenueReportControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_sums_the_period_and_breaks_it_down_largest_first(): void
    {
        $office = RevenueImport::factory()->create();
        $unit = RevenueImport::factory()->counter('keliling-01', 'Samsat Keliling 01')->create();
        RevenueTransaction::factory()->for($office)->count(2)->create();
        RevenueTransaction::factory()->for($office)->paying(['bbn' => 9000000, 'opsen_bbn' => 5940000])->create([
            'jenis_daftar' => 'BARU', 'kecamatan' => 'KELAPA LIMA', 'kelurahan' => 'Oesapa',
        ]);
        RevenueTransaction::factory()->for($unit)->paying(['pkb' => 100000, 'denda_pkb' => 2000, 'opsen_pkb' => 66000, 'opsen_denda_pkb' => 1320])->create([
            'loket' => 'keliling-01', 'tahun_tunggakan' => 1,
        ]);
        RevenueTransaction::factory()->for($office)->create(['tanggal' => '2026-10-15']);

        $response = $this->report(['dari' => '2026-10-14', 'sampai' => '2026-10-14']);

        $response->assertOk()
            ->assertJsonPath('dari', '2026-10-14')
            ->assertJsonPath('sampai', '2026-10-14')
            ->assertJsonPath('ringkasan', [
                'transaksi' => 4, 'pkb' => 700000, 'bbn' => 9000000, 'dendaPkb' => 2000, 'dendaBbn' => 0, 'tunggakan' => 0, 'dendaTunggakan' => 0,
                'opsenPkb' => 462000, 'opsenBbn' => 5940000, 'opsenDendaPkb' => 1320, 'opsenDendaBbn' => 0, 'opsenTunggakan' => 0,
                'opsenDendaTunggakan' => 0, 'jumlah' => 16105320,
            ]);
        $this->assertSame([
            ['nilai' => 'kantor', 'nama' => 'Loket SAMSAT', 'transaksi' => 3, 'jumlah' => 15936000],
            ['nilai' => 'keliling-01', 'nama' => 'Samsat Keliling 01', 'transaksi' => 1, 'jumlah' => 169320],
        ], $this->rows($response, 'loket', ['nilai', 'nama', 'transaksi', 'jumlah']));
        $this->assertSame([
            ['nilai' => 'BARU', 'transaksi' => 1, 'jumlah' => 14940000],
            ['nilai' => 'Teliti Ulang / Pengesahan', 'transaksi' => 3, 'jumlah' => 1165320],
        ], $this->rows($response, 'jenisDaftar'));
        $this->assertSame([
            ['nilai' => 'Oesapa', 'kecamatan' => 'KELAPA LIMA', 'transaksi' => 1, 'jumlah' => 14940000],
            ['nilai' => 'Oebobo', 'kecamatan' => 'OEBOBO', 'transaksi' => 3, 'jumlah' => 1165320],
        ], $this->rows($response, 'kelurahan', ['nilai', 'kecamatan', 'transaksi', 'jumlah']));
        $this->assertSame([['nilai' => 'pribadi', 'transaksi' => 4, 'jumlah' => 16105320]], $this->rows($response, 'kepemilikan'));
        $this->assertSame([['nilai' => '2026-10-14', 'transaksi' => 4, 'jumlah' => 16105320]], $this->rows($response, 'tanggal'));
        $this->assertSame(9000000, $response->json('rincian.jenisDaftar.0.bbn'));
    }

    public function test_orders_equal_totals_by_transactions_then_by_name(): void
    {
        $import = RevenueImport::factory()->create();
        RevenueTransaction::factory()->recycle($import)->create(['merk' => 'YAMAHA']);
        RevenueTransaction::factory()->recycle($import)->create(['merk' => 'HONDA']);
        RevenueTransaction::factory()->recycle($import)->count(2)->paying(['pkb' => 249000])->create(['merk' => 'SUZUKI']);

        $response = $this->report(['dari' => '2026-10-14', 'sampai' => '2026-10-14']);

        $this->assertSame(['SUZUKI', 'HONDA', 'YAMAHA'], array_column($response->json('rincian.merk'), 'nilai'));
    }

    public function test_names_each_counter_as_its_latest_import_did(): void
    {
        RevenueImport::factory()->counter('gerai:samsat-corner', 'SAMSAT CORNER LAMA')->create(['created_at' => '2026-10-01 08:00:00']);
        $latest = RevenueImport::factory()->counter('gerai:samsat-corner', 'Samsat Corner')->create(['created_at' => '2026-10-14 08:00:00']);
        RevenueTransaction::factory()->for($latest)->create(['loket' => 'gerai:samsat-corner']);

        $response = $this->report(['dari' => '2026-10-14', 'sampai' => '2026-10-14']);

        $response->assertJsonPath('rincian.loket.0.nama', 'Samsat Corner')
            ->assertJsonPath('pilihan.loket', [['nilai' => 'gerai:samsat-corner', 'nama' => 'Samsat Corner']]);
    }

    public function test_keeps_the_transactions_matching_every_filter_and_offers_every_value_of_the_period(): void
    {
        $import = RevenueImport::factory()->create();
        RevenueTransaction::factory()->recycle($import)->create(['jenis_daftar' => 'BARU', 'kecamatan' => 'OEBOBO']);
        RevenueTransaction::factory()->recycle($import)->create(['jenis_daftar' => 'BARU', 'kecamatan' => 'MAULAFA']);
        RevenueTransaction::factory()->recycle($import)->create(['jenis_daftar' => 'MUTASI MASUK', 'kecamatan' => 'OEBOBO']);
        RevenueTransaction::factory()->recycle($import)->create(['jenis_daftar' => 'Teliti Ulang / Pengesahan', 'kecamatan' => 'OEBOBO']);
        RevenueTransaction::factory()->recycle($import)->create(['jenis_daftar' => 'BALIK NAMA', 'tanggal' => '2026-10-15']);

        $response = $this->report([
            'dari' => '2026-10-14', 'sampai' => '2026-10-14', 'jenisDaftar' => ['BARU', 'MUTASI MASUK'], 'kecamatan' => ['OEBOBO'],
        ]);

        $response->assertOk()
            ->assertJsonPath('ringkasan.transaksi', 2)
            ->assertJsonPath('pilihan.jenisDaftar', ['BARU', 'MUTASI MASUK', 'Teliti Ulang / Pengesahan'])
            ->assertJsonPath('pilihan.kecamatan', ['MAULAFA', 'OEBOBO']);
        $this->assertSame([
            ['nilai' => 'BARU', 'transaksi' => 1, 'jumlah' => 498000],
            ['nilai' => 'MUTASI MASUK', 'transaksi' => 1, 'jumlah' => 498000],
        ], $this->rows($response, 'jenisDaftar'));
    }

    #[TestWith(['ada', 1])]
    #[TestWith(['tidak', 2])]
    public function test_keeps_the_transactions_with_or_without_arrears(string $arrears, int $transactions): void
    {
        $import = RevenueImport::factory()->create();
        RevenueTransaction::factory()->recycle($import)->count(2)->create();
        RevenueTransaction::factory()->recycle($import)->paying(['tunggakan' => 300000, 'opsen_tunggakan' => 198000])->create(['tahun_tunggakan' => 1]);

        $response = $this->report(['dari' => '2026-10-14', 'sampai' => '2026-10-14', 'tunggakan' => $arrears]);

        $response->assertOk()->assertJsonPath('ringkasan.transaksi', $transactions);
    }

    public function test_compares_a_day_with_the_latest_earlier_day_with_matching_transactions(): void
    {
        $import = RevenueImport::factory()->create();
        RevenueTransaction::factory()->recycle($import)->count(2)->create(['tanggal' => '2026-10-12']);
        RevenueTransaction::factory()->recycle($import)->create(['tanggal' => '2026-10-13', 'jenis_daftar' => 'BARU']);
        RevenueTransaction::factory()->recycle($import)->create();

        $this->report(['dari' => '2026-10-14', 'sampai' => '2026-10-14'])
            ->assertJsonPath('pembanding.dari', '2026-10-13')
            ->assertJsonPath('pembanding.sampai', '2026-10-13')
            ->assertJsonPath('pembanding.ringkasan.transaksi', 1);
        $this->report(['dari' => '2026-10-14', 'sampai' => '2026-10-14', 'jenisDaftar' => ['Teliti Ulang / Pengesahan']])
            ->assertJsonPath('pembanding.dari', '2026-10-12')
            ->assertJsonPath('pembanding.sampai', '2026-10-12')
            ->assertJsonPath('pembanding.ringkasan.transaksi', 2)
            ->assertJsonPath('pembanding.ringkasan.jumlah', 996000);
        $this->report(['dari' => '2026-10-12', 'sampai' => '2026-10-12'])->assertJsonPath('pembanding', null);
    }

    #[TestWith(['2026-09-01', '2026-09-30', '2026-08-01', '2026-08-31'])]
    #[TestWith(['2026-03-01', '2026-03-31', '2026-02-01', '2026-02-28'])]
    #[TestWith(['2026-07-01', '2026-09-30', '2026-04-01', '2026-06-30'])]
    #[TestWith(['2025-11-01', '2026-02-28', '2025-07-01', '2025-10-31'])]
    #[TestWith(['2026-10-01', '2026-10-14', '2026-09-17', '2026-09-30'])]
    #[TestWith(['2026-10-01', '2026-10-30', '2026-09-01', '2026-09-30'])]
    #[TestWith(['2026-09-02', '2026-09-30', '2026-08-04', '2026-09-01'])]
    public function test_compares_whole_months_with_the_months_before_and_another_period_with_the_days_before(string $from, string $until, string $start, string $end): void
    {
        $import = RevenueImport::factory()->create();
        foreach ([$start, $end, Carbon::parse($start)->subDay()->toDateString(), Carbon::parse($end)->addDay()->toDateString()] as $date) {
            RevenueTransaction::factory()->recycle($import)->create(['tanggal' => $date]);
        }

        $this->report(['dari' => $from, 'sampai' => $until])
            ->assertOk()
            ->assertJsonPath('pembanding.dari', $start)
            ->assertJsonPath('pembanding.sampai', $end)
            ->assertJsonPath('pembanding.ringkasan.transaksi', 2);
    }

    public function test_lists_the_daily_totals_of_a_period_in_date_order(): void
    {
        $import = RevenueImport::factory()->create();
        RevenueTransaction::factory()->recycle($import)->create(['tanggal' => '2026-09-30']);
        RevenueTransaction::factory()->recycle($import)->count(2)->create(['tanggal' => '2026-09-01']);
        RevenueTransaction::factory()->recycle($import)->create(['tanggal' => '2026-10-01']);

        $response = $this->report(['dari' => '2026-09-01', 'sampai' => '2026-09-30']);

        $this->assertSame([
            ['nilai' => '2026-09-01', 'transaksi' => 2, 'jumlah' => 996000],
            ['nilai' => '2026-09-30', 'transaksi' => 1, 'jumlah' => 498000],
        ], $this->rows($response, 'tanggal'));
        $response->assertJsonPath('loketBuka', []);
    }

    public function test_lists_the_counters_serving_on_the_day(): void
    {
        MobileServiceStop::factory()->outlet('Samsat Corner', [3])->create();
        MobileServiceStop::factory()->outlet('Mall Pelayanan Publik', [1])->create();
        MobileServiceStop::factory()->on('2026-10-14', '02')->create();
        MobileServiceStop::factory()->counter('2026-10-12', '2026-10-16', 'Pameran Pembangunan')->inactive()->create();

        $response = $this->report(['dari' => '2026-10-14', 'sampai' => '2026-10-14']);

        $response->assertOk()->assertJsonPath('loketBuka', [
            ['kunci' => 'kantor', 'nama' => 'Loket SAMSAT', 'jenis' => 'kantor'],
            ['kunci' => 'keliling-02', 'nama' => 'Samsat Keliling 02', 'jenis' => 'keliling'],
            ['kunci' => 'gerai:samsat-corner', 'nama' => 'Samsat Corner', 'jenis' => 'gerai'],
        ]);
    }

    public function test_reports_an_empty_period_as_zeros(): void
    {
        $response = $this->report(['dari' => '2024-01-01', 'sampai' => '2024-12-31']);

        $response->assertOk()
            ->assertJsonPath('ringkasan.transaksi', 0)
            ->assertJsonPath('ringkasan.jumlah', 0)
            ->assertJsonPath('pembanding.dari', '2023-01-01')
            ->assertJsonPath('pembanding.sampai', '2023-12-31')
            ->assertJsonPath('rincian.jenisDaftar', [])
            ->assertJsonPath('pilihan.loket', []);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    #[TestWith([['sampai' => '2026-10-14'], 'dari', 'Pilih tanggal awal laporan.'])]
    #[TestWith([['dari' => '2026-10-14'], 'sampai', 'Pilih tanggal akhir laporan.'])]
    #[TestWith([['dari' => '2026-02-30', 'sampai' => '2026-10-14'], 'dari', 'Tanggal awal laporan tidak valid.'])]
    #[TestWith([['dari' => '2026-10-14', 'sampai' => '14-10-2026'], 'sampai', 'Tanggal akhir laporan tidak valid.'])]
    #[TestWith([['dari' => '2026-10-14', 'sampai' => '2026-10-13'], 'sampai', 'Tanggal akhir tidak boleh sebelum tanggal awal.'])]
    #[TestWith([['dari' => '2025-10-13', 'sampai' => '2026-10-14'], 'sampai', 'Rentang laporan paling lama satu tahun.'])]
    #[TestWith([['dari' => '2026-10-14', 'sampai' => '2026-10-14', 'tunggakan' => 'semua'], 'tunggakan', 'Saringan tunggakan tidak dikenal.'])]
    #[TestWith([['dari' => '2026-10-14', 'sampai' => '2026-10-14', 'loket' => 'kantor'], 'loket', 'Saringan loket tidak valid.'])]
    #[TestWith([['dari' => '2026-10-14', 'sampai' => '2026-10-14', 'jenisDaftar' => [['BARU']]], 'jenisDaftar.0', 'Saringan jenis daftar tidak valid.'])]
    public function test_rejects_an_invalid_period_or_filter(array $query, string $field, string $message): void
    {
        $this->report($query)->assertUnprocessable()->assertJsonValidationErrors([$field => $message]);
    }

    public function test_rejects_an_overlong_filter(): void
    {
        $this->report(['dari' => '2026-10-14', 'sampai' => '2026-10-14', 'uptAsal' => [str_repeat('a', 81)]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['uptAsal.0' => 'Saringan asal UPT terlalu panjang.']);
        $this->report(['dari' => '2026-10-14', 'sampai' => '2026-10-14', 'merk' => array_fill(0, 501, 'HONDA')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['merk' => 'Saringan merek terlalu panjang.']);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function report(array $query): TestResponse
    {
        return $this->actingAs(User::factory()->withModules(DashboardModule::Penerimaan)->create())
            ->getJson('/api/penerimaan/laporan?'.http_build_query($query));
    }

    /**
     * The chosen fields of each row of a breakdown.
     *
     * @param  list<string>  $fields
     * @return list<array<string, mixed>>
     */
    private function rows(TestResponse $response, string $breakdown, array $fields = ['nilai', 'transaksi', 'jumlah']): array
    {
        return array_map(fn (array $row): array => Arr::only($row, $fields), $response->json("rincian.{$breakdown}"));
    }
}
