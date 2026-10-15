<?php

namespace Tests\Feature;

use App\Models\RevenueImport;
use App\Models\RevenueTransaction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PublicRevenueReportTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_reports_nothing_until_an_import_is_shown_on_the_portal(): void
    {
        RevenueTransaction::factory()->recycle(RevenueImport::factory()->create())->count(5)->create();

        $response = $this->report();

        $response->assertOk()->assertExactJson([
            'tersedia' => null,
            'dari' => null,
            'sampai' => null,
            'ringkasan' => null,
            'disamarkan' => false,
            'pembanding' => null,
            'rincian' => [],
            'pilihan' => ['loket' => [], 'jenisDaftar' => [], 'jenisKendaraan' => [], 'kecamatan' => [], 'kelurahan' => []],
            'minimum' => 3,
        ]);
        $this->assertSame([], $response->headers->getCookies());
    }

    public function test_reports_the_imports_shown_on_the_portal_from_their_latest_day(): void
    {
        $shown = RevenueImport::factory()->create(['tampil' => true]);
        $hidden = RevenueImport::factory()->create();
        RevenueTransaction::factory()->recycle($shown)->count(3)->create(['tanggal' => '2026-10-13']);
        RevenueTransaction::factory()->recycle($shown)->count(4)->create();
        RevenueTransaction::factory()->recycle($hidden)->count(5)->create();
        RevenueTransaction::factory()->recycle($hidden)->count(3)->create(['tanggal' => '2026-10-15']);

        $this->report()
            ->assertOk()
            ->assertJsonPath('tersedia', ['awal' => '2026-10-13', 'akhir' => '2026-10-14'])
            ->assertJsonPath('dari', '2026-10-14')
            ->assertJsonPath('sampai', '2026-10-14')
            ->assertJsonPath('ringkasan.transaksi', 4)
            ->assertJsonPath('ringkasan.jumlah', 4 * 498000)
            ->assertJsonPath('disamarkan', false)
            ->assertJsonPath('pembanding.dari', '2026-10-13')
            ->assertJsonPath('pembanding.ringkasan.transaksi', 3)
            ->assertJsonPath('rincian.loket.0', ['nilai' => 'kantor', 'nama' => 'Loket SAMSAT', ...$this->sums(4)])
            ->assertJsonPath('rincian.tanggal', [['nilai' => '2026-10-14', ...$this->sums(4)]])
            ->assertJsonStructure(['rincian' => ['loket', 'jenisDaftar', 'jenisKendaraan', 'kecamatan', 'kelurahan', 'tanggal']])
            ->assertJsonMissingPath('rincian.merk')
            ->assertJsonMissingPath('loketBuka');
    }

    public function test_merges_the_groups_of_fewer_than_three_transactions_into_one_masked_row(): void
    {
        $import = RevenueImport::factory()->create(['tampil' => true]);
        RevenueTransaction::factory()->recycle($import)->count(5)->create(['kecamatan' => 'OEBOBO', 'kelurahan' => 'Oebobo']);
        RevenueTransaction::factory()->recycle($import)->create(['kecamatan' => 'MAULAFA', 'kelurahan' => 'Penfui']);
        RevenueTransaction::factory()->recycle($import)->count(2)->create(['kecamatan' => 'ALAK', 'kelurahan' => 'Manulai II', 'loket' => 'keliling-01']);

        $response = $this->report();

        $this->assertSame([
            ['nilai' => 'OEBOBO', 'transaksi' => 5, 'jumlah' => 5 * 498000],
            ['nilai' => 'Lainnya (disamarkan)', 'transaksi' => 3, 'jumlah' => 3 * 498000, 'disamarkan' => true, 'kelompok' => 2],
        ], $this->rows($response, 'kecamatan', ['nilai', 'transaksi', 'jumlah', 'disamarkan', 'kelompok']));
        $response->assertJsonPath('rincian.kecamatan.1.pkb', 3 * 300000)
            ->assertJsonPath('rincian.kelurahan.0.kecamatan', 'OEBOBO')
            ->assertJsonPath('rincian.kelurahan.1.nilai', 'Lainnya (disamarkan)')
            // Two counters only: hiding the unit's 2 transactions hides the office too, or the total would give them away
            ->assertJsonPath('rincian.loket', [['nilai' => 'Lainnya (disamarkan)', 'nama' => 'Lainnya (disamarkan)', ...$this->sums(8), 'disamarkan' => true, 'kelompok' => 2]])
            ->assertJsonPath('pilihan.kecamatan', ['OEBOBO'])
            ->assertJsonPath('pilihan.kelurahan', ['Oebobo']);
        $this->assertArrayHasKey('kecamatan', $response->json('rincian.kelurahan.1'));
        $this->assertNull($response->json('rincian.kelurahan.1.kecamatan'));
        $this->assertSame(['loket', 'jenisDaftar', 'jenisKendaraan', 'kecamatan', 'kelurahan'], array_keys($response->json('pilihan')));
    }

    public function test_also_masks_the_smallest_shown_group_when_the_masked_ones_are_fewer_than_three_together(): void
    {
        $import = RevenueImport::factory()->create(['tampil' => true]);
        RevenueTransaction::factory()->recycle($import)->count(5)->create(['jenis_kendaraan' => 'SEPEDA MOTOR']);
        RevenueTransaction::factory()->recycle($import)->count(3)->paying(['pkb' => 2000000])->create(['jenis_kendaraan' => 'MINIBUS']);
        RevenueTransaction::factory()->recycle($import)->count(4)->paying(['pkb' => 2500000])->create(['jenis_kendaraan' => 'PICK UP']);
        RevenueTransaction::factory()->recycle($import)->create(['jenis_kendaraan' => 'TRUCK']);

        $response = $this->report();

        // MINIBUS joins the masked row: the fewest transactions, though not the smallest sum.
        // The shown groups keep their order, largest sum first.
        $this->assertSame([
            ['nilai' => 'PICK UP', 'transaksi' => 4],
            ['nilai' => 'SEPEDA MOTOR', 'transaksi' => 5],
            ['nilai' => 'Lainnya (disamarkan)', 'transaksi' => 4, 'kelompok' => 2],
        ], $this->rows($response, 'jenisKendaraan', ['nilai', 'transaksi', 'kelompok']));
    }

    public function test_hides_a_result_of_fewer_than_three_transactions_but_reports_an_empty_one(): void
    {
        $import = RevenueImport::factory()->create(['tampil' => true]);
        RevenueTransaction::factory()->recycle($import)->count(4)->create(['tanggal' => '2026-10-13']);
        RevenueTransaction::factory()->recycle($import)->count(4)->create(['tanggal' => '2026-10-13', 'kelurahan' => 'Penfui']);
        RevenueTransaction::factory()->recycle($import)->count(3)->create();
        RevenueTransaction::factory()->recycle($import)->count(2)->create(['kelurahan' => 'Penfui']);

        $this->report(['dari' => '2026-10-14', 'sampai' => '2026-10-14', 'kelurahan' => ['Penfui']])
            ->assertOk()
            ->assertJsonPath('ringkasan', null)
            ->assertJsonPath('disamarkan', true)
            ->assertJsonPath('pembanding', null)
            ->assertJsonPath('rincian', [])
            ->assertJsonPath('pilihan.kelurahan', ['Oebobo']);
        $this->report(['dari' => '2026-10-14', 'sampai' => '2026-10-14', 'kelurahan' => ['Oesapa']])
            ->assertOk()
            ->assertJsonPath('ringkasan.transaksi', 0)
            ->assertJsonPath('disamarkan', false)
            ->assertJsonPath('rincian.kelurahan', []);
    }

    public function test_hides_a_comparison_of_fewer_than_three_transactions(): void
    {
        $import = RevenueImport::factory()->create(['tampil' => true]);
        RevenueTransaction::factory()->recycle($import)->count(2)->create(['tanggal' => '2026-10-13']);
        RevenueTransaction::factory()->recycle($import)->count(3)->create();

        $this->report(['dari' => '2026-10-14', 'sampai' => '2026-10-14'])
            ->assertJsonPath('pembanding', ['dari' => '2026-10-13', 'sampai' => '2026-10-13', 'ringkasan' => null]);
    }

    public function test_keeps_the_dates_of_days_with_too_few_transactions_without_their_figures(): void
    {
        $import = RevenueImport::factory()->create(['tampil' => true]);
        RevenueTransaction::factory()->recycle($import)->count(4)->create(['tanggal' => '2026-10-01']);
        RevenueTransaction::factory()->recycle($import)->create(['tanggal' => '2026-10-02']);
        RevenueTransaction::factory()->recycle($import)->count(5)->create(['tanggal' => '2026-10-05']);

        $this->report(['dari' => '2026-10-01', 'sampai' => '2026-10-31'])
            ->assertOk()
            ->assertJsonPath('ringkasan.transaksi', 10)
            ->assertJsonPath('rincian.tanggal', [
                ['nilai' => '2026-10-01', 'disamarkan' => true],
                ['nilai' => '2026-10-02', 'disamarkan' => true],
                ['nilai' => '2026-10-05', ...$this->sums(5)],
            ]);
    }

    public function test_keeps_only_the_transactions_matching_the_filters(): void
    {
        $import = RevenueImport::factory()->create(['tampil' => true]);
        RevenueTransaction::factory()->recycle($import)->count(3)->create(['jenis_daftar' => 'BARU']);
        RevenueTransaction::factory()->recycle($import)->count(4)->create(['jenis_daftar' => 'BARU', 'kecamatan' => 'ALAK']);
        RevenueTransaction::factory()->recycle($import)->count(5)->create();

        $this->report(['dari' => '2026-10-14', 'sampai' => '2026-10-14', 'jenisDaftar' => ['BARU'], 'kecamatan' => ['OEBOBO', 'MAULAFA']])
            ->assertOk()
            ->assertJsonPath('ringkasan.transaksi', 3)
            ->assertJsonPath('pilihan.jenisDaftar', ['BARU', 'Teliti Ulang / Pengesahan']);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    #[TestWith([['tunggakan' => 'ada'], 'query', 'Parameter kueri tidak didukung.'])]
    #[TestWith([['merk' => ['HONDA']], 'query', 'Parameter kueri tidak didukung.'])]
    #[TestWith([['dari' => '2026-10-14'], 'sampai', 'Pilih tanggal akhir laporan.'])]
    #[TestWith([['sampai' => '2026-10-14'], 'dari', 'Pilih tanggal awal laporan.'])]
    #[TestWith([['dari' => '2026-02-30', 'sampai' => '2026-10-14'], 'dari', 'Tanggal awal laporan tidak valid.'])]
    #[TestWith([['dari' => '2026-10-14', 'sampai' => '2026-10-13'], 'sampai', 'Tanggal akhir tidak boleh sebelum tanggal awal.'])]
    #[TestWith([['dari' => '2025-10-13', 'sampai' => '2026-10-14'], 'sampai', 'Rentang laporan paling lama satu tahun.'])]
    #[TestWith([['kelurahan' => 'Oebobo'], 'kelurahan', 'Saringan kelurahan tidak valid.'])]
    public function test_rejects_an_unknown_parameter_or_an_invalid_period_or_filter(array $query, string $field, string $message): void
    {
        $this->report($query)->assertUnprocessable()->assertJsonValidationErrors([$field => $message]);
    }

    public function test_accepts_a_period_of_366_days(): void
    {
        $this->report(['dari' => '2025-10-14', 'sampai' => '2026-10-14'])->assertOk();
    }

    public function test_rejects_an_overlong_filter(): void
    {
        $this->report(['jenisKendaraan' => array_fill(0, 101, 'MINIBUS')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['jenisKendaraan' => 'Saringan jenis kendaraan terlalu panjang.']);
        $this->report(['loket' => [str_repeat('a', 81)]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['loket.0' => 'Saringan loket terlalu panjang.']);
    }

    public function test_answers_at_most_60_reports_a_minute_from_one_address(): void
    {
        for ($request = 0; $request < 60; $request++) {
            $this->report()->assertOk();
        }

        $this->report()->assertTooManyRequests()->assertExactJson(['message' => 'Terlalu banyak permintaan. Silakan coba lagi sebentar lagi.']);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function report(array $query = []): TestResponse
    {
        return $this->getJson('/api/public/penerimaan'.($query === [] ? '' : '?'.http_build_query($query)));
    }

    /**
     * The sums of so many default transactions.
     *
     * @return array<string, int>
     */
    private function sums(int $transactions): array
    {
        return [
            'transaksi' => $transactions, 'pkb' => 300000 * $transactions, 'bbn' => 0, 'dendaPkb' => 0, 'dendaBbn' => 0, 'tunggakan' => 0,
            'dendaTunggakan' => 0, 'opsenPkb' => 198000 * $transactions, 'opsenBbn' => 0, 'opsenDendaPkb' => 0, 'opsenDendaBbn' => 0,
            'opsenTunggakan' => 0, 'opsenDendaTunggakan' => 0, 'jumlah' => 498000 * $transactions,
        ];
    }

    /**
     * The chosen fields of each row of a breakdown.
     *
     * @param  list<string>  $fields
     * @return list<array<string, mixed>>
     */
    private function rows(TestResponse $response, string $breakdown, array $fields): array
    {
        return array_map(fn (array $row): array => Arr::only($row, $fields), $response->json("rincian.{$breakdown}"));
    }
}
