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
            'periode' => null,
            'dari' => null,
            'sampai' => null,
            'ringkasan' => null,
            'kendaraanBaru' => null,
            'disamarkan' => false,
            'pembanding' => null,
            'rincian' => [],
            'minimum' => 3,
        ]);
        $this->assertSame([], $response->headers->getCookies());
    }

    public function test_reports_the_latest_published_day_with_its_totals_only(): void
    {
        $shown = RevenueImport::factory()->create(['tampil' => true]);
        $hidden = RevenueImport::factory()->create();
        RevenueTransaction::factory()->recycle($shown)->count(3)->create(['tanggal' => '2026-10-13']);
        RevenueTransaction::factory()->recycle($shown)->count(3)->create();
        RevenueTransaction::factory()->recycle($shown)->paying(['bbn' => 2000000, 'opsen_bbn' => 1320000])->create(['jenis_daftar' => 'BARU']);
        RevenueTransaction::factory()->recycle($hidden)->count(5)->create();
        RevenueTransaction::factory()->recycle($hidden)->count(3)->create(['tanggal' => '2026-10-15']);

        $this->report()->assertOk()->assertExactJson([
            'tersedia' => ['awal' => '2026-10-13', 'akhir' => '2026-10-14'],
            'periode' => 'harian',
            'dari' => '2026-10-14',
            'sampai' => '2026-10-14',
            'ringkasan' => [
                ...$this->sums(4),
                'pkb' => 3 * 300000, 'bbn' => 2000000, 'opsenPkb' => 3 * 198000, 'opsenBbn' => 1320000, 'jumlah' => 3 * 498000 + 3320000,
            ],
            'kendaraanBaru' => ['transaksi' => 1, 'jumlah' => 3320000],
            'disamarkan' => false,
            'pembanding' => ['dari' => '2026-10-13', 'sampai' => '2026-10-13', 'ringkasan' => ['transaksi' => 3, 'jumlah' => 3 * 498000]],
            'rincian' => [],
            'minimum' => 3,
        ]);
    }

    public function test_reports_a_whole_month_with_each_group_s_transactions_and_sum_only(): void
    {
        $import = RevenueImport::factory()->create(['tampil' => true]);
        RevenueTransaction::factory()->recycle($import)->count(3)->create(['tanggal' => '2026-02-02']);
        RevenueTransaction::factory()->recycle($import)->count(4)->create(['tanggal' => '2026-02-27']);
        RevenueTransaction::factory()->recycle($import)->count(5)->create(['tanggal' => '2026-03-02']);

        $this->report(['bulan' => '2026-02'])
            ->assertOk()
            ->assertJsonPath('periode', 'bulanan')
            ->assertJsonPath('dari', '2026-02-01')
            ->assertJsonPath('sampai', '2026-02-28')
            ->assertJsonPath('ringkasan', $this->sums(7))
            ->assertJsonPath('kendaraanBaru', ['transaksi' => 0, 'jumlah' => 0])
            ->assertJsonPath('pembanding', ['dari' => '2026-01-01', 'sampai' => '2026-01-31', 'ringkasan' => ['transaksi' => 0, 'jumlah' => 0]])
            ->assertJsonPath('rincian', [
                'loket' => [['nilai' => 'kantor', 'nama' => 'Loket SAMSAT', ...$this->figures(7)]],
                'jenisDaftar' => [['nilai' => 'Teliti Ulang / Pengesahan', ...$this->figures(7)]],
                'jenisKendaraan' => [['nilai' => 'SEPEDA MOTOR', ...$this->figures(7)]],
                'kecamatan' => [['nilai' => 'OEBOBO', ...$this->figures(7)]],
                'kelurahan' => [['nilai' => 'Oebobo', 'kecamatan' => 'OEBOBO', ...$this->figures(7)]],
                'tanggal' => [['nilai' => '2026-02-02', ...$this->figures(3)], ['nilai' => '2026-02-27', ...$this->figures(4)]],
            ]);
        $this->report(['bulan' => '2026-03'])
            ->assertJsonPath('pembanding.ringkasan', $this->figures(7));
    }

    public function test_never_shows_the_details_kept_for_rincian_penerimaan(): void
    {
        $import = RevenueImport::factory()->create(['tampil' => true]);
        RevenueTransaction::factory()->recycle($import)->withDetails()->count(4)->create();

        foreach ([[], ['tanggal' => '2026-10-14'], ['bulan' => '2026-10']] as $query) {
            $content = $this->report($query)->assertOk()->getContent();
            foreach (['DH1234XY', 'PEMILIK RAHASIA', 'TIPE UJI', 'SEPEDA MOTOR PRIBADI', '"rt"', '"rw"', '"nopol"'] as $secret) {
                $this->assertStringNotContainsString($secret, $content);
            }
        }
        $summary = $this->getJson('/api/public/penerimaan/ringkasan')->assertOk()->getContent();
        $this->assertStringNotContainsString('PEMILIK RAHASIA', $summary);
        $this->assertStringNotContainsString('DH1234XY', $summary);
    }

    public function test_merges_the_groups_of_fewer_than_three_transactions_into_one_masked_row(): void
    {
        $import = RevenueImport::factory()->create(['tampil' => true]);
        RevenueTransaction::factory()->recycle($import)->count(5)->create();
        RevenueTransaction::factory()->recycle($import)->create(['jenis_daftar' => 'BARU']);
        RevenueTransaction::factory()->recycle($import)->count(2)->create(['jenis_daftar' => 'JUAL BELI', 'loket' => 'keliling-01']);

        $response = $this->report(['bulan' => '2026-10']);

        $response->assertJsonPath('rincian.jenisDaftar', [
            ['nilai' => 'Teliti Ulang / Pengesahan', ...$this->figures(5)],
            ['nilai' => 'Lainnya (disamarkan)', ...$this->figures(3), 'disamarkan' => true, 'kelompok' => 2],
        ]);
        // Two counters only: hiding the unit's 2 transactions hides the office too, or the total would give them away
        $response->assertJsonPath('rincian.loket', [
            ['nilai' => 'Lainnya (disamarkan)', 'nama' => 'Lainnya (disamarkan)', ...$this->figures(8), 'disamarkan' => true, 'kelompok' => 2],
        ]);
        // So is the one new vehicle
        $response->assertJsonPath('kendaraanBaru', null);
    }

    public function test_also_masks_the_smallest_shown_group_when_the_masked_ones_are_fewer_than_three_together(): void
    {
        $import = RevenueImport::factory()->create(['tampil' => true]);
        RevenueTransaction::factory()->recycle($import)->count(5)->create(['jenis_kendaraan' => 'SEPEDA MOTOR']);
        RevenueTransaction::factory()->recycle($import)->count(3)->paying(['pkb' => 2000000])->create(['jenis_kendaraan' => 'MINIBUS']);
        RevenueTransaction::factory()->recycle($import)->count(4)->paying(['pkb' => 2500000])->create(['jenis_kendaraan' => 'PICK UP']);
        RevenueTransaction::factory()->recycle($import)->create(['jenis_kendaraan' => 'TRUCK']);

        $response = $this->report(['bulan' => '2026-10']);

        // MINIBUS joins the masked row: the fewest transactions, though not the smallest sum.
        // The shown groups keep their order, largest sum first.
        $this->assertSame([
            ['nilai' => 'PICK UP', 'transaksi' => 4],
            ['nilai' => 'SEPEDA MOTOR', 'transaksi' => 5],
            ['nilai' => 'Lainnya (disamarkan)', 'transaksi' => 4, 'kelompok' => 2],
        ], $this->rows($response, 'jenisKendaraan', ['nilai', 'transaksi', 'kelompok']));
    }

    public function test_hides_a_kecamatan_s_kelurahan_with_it_and_enough_of_them_to_keep_one_from_its_total(): void
    {
        $import = RevenueImport::factory()->create(['tampil' => true]);
        RevenueTransaction::factory()->recycle($import)->count(6)->create();
        RevenueTransaction::factory()->recycle($import)->count(4)->create(['kelurahan' => 'Fatululi']);
        RevenueTransaction::factory()->recycle($import)->create(['kelurahan' => 'Oetete']);
        RevenueTransaction::factory()->recycle($import)->count(5)->create(['kecamatan' => 'KELAPA LIMA', 'kelurahan' => 'Oesapa']);
        RevenueTransaction::factory()->recycle($import)->count(3)->create(['kecamatan' => 'MAULAFA', 'kelurahan' => 'Penfui']);
        RevenueTransaction::factory()->recycle($import)->create(['kecamatan' => 'ALAK', 'kelurahan' => 'Manulai II']);

        $response = $this->report(['bulan' => '2026-10']);

        // ALAK's one transaction takes MAULAFA with it
        $this->assertSame([
            ['nilai' => 'OEBOBO', 'transaksi' => 11],
            ['nilai' => 'KELAPA LIMA', 'transaksi' => 5],
            ['nilai' => 'Lainnya (disamarkan)', 'transaksi' => 4, 'kelompok' => 2],
        ], $this->rows($response, 'kecamatan', ['nilai', 'transaksi', 'kelompok']));
        // Penfui hides with MAULAFA; Fatululi with Oetete, or OEBOBO's total would give Oetete away
        $this->assertSame([
            ['nilai' => 'Oebobo', 'kecamatan' => 'OEBOBO', 'transaksi' => 6],
            ['nilai' => 'Oesapa', 'kecamatan' => 'KELAPA LIMA', 'transaksi' => 5],
            ['nilai' => 'Lainnya (disamarkan)', 'kecamatan' => null, 'transaksi' => 9, 'kelompok' => 4],
        ], $this->rows($response, 'kelurahan', ['nilai', 'kecamatan', 'transaksi', 'kelompok']));
    }

    public function test_hides_the_days_hidden_in_their_month_in_the_daily_report_too(): void
    {
        $import = RevenueImport::factory()->create(['tampil' => true]);
        RevenueTransaction::factory()->recycle($import)->create(['tanggal' => '2026-10-01']);
        RevenueTransaction::factory()->recycle($import)->count(4)->create(['tanggal' => '2026-10-02']);
        RevenueTransaction::factory()->recycle($import)->count(5)->create(['tanggal' => '2026-10-05']);

        // 1 October's one transaction takes 2 October with it
        $this->report(['bulan' => '2026-10'])
            ->assertJsonPath('ringkasan.transaksi', 10)
            ->assertJsonPath('rincian.tanggal', [
                ['nilai' => '2026-10-01', 'disamarkan' => true],
                ['nilai' => '2026-10-02', 'disamarkan' => true],
                ['nilai' => '2026-10-05', ...$this->figures(5)],
            ]);
        $this->report(['tanggal' => '2026-10-02'])
            ->assertOk()
            ->assertJsonPath('ringkasan', null)
            ->assertJsonPath('kendaraanBaru', null)
            ->assertJsonPath('disamarkan', true)
            ->assertJsonPath('pembanding', null);
        $this->report(['tanggal' => '2026-10-05'])
            ->assertJsonPath('ringkasan.transaksi', 5)
            ->assertJsonPath('pembanding', ['dari' => '2026-10-02', 'sampai' => '2026-10-02', 'ringkasan' => null]);
    }

    public function test_reports_a_day_without_transactions_as_zeros_and_hides_a_month_of_fewer_than_three(): void
    {
        $import = RevenueImport::factory()->create(['tampil' => true]);
        RevenueTransaction::factory()->recycle($import)->count(2)->create(['tanggal' => '2026-09-30']);
        RevenueTransaction::factory()->recycle($import)->count(3)->create();

        $this->report(['tanggal' => '2026-10-11'])
            ->assertJsonPath('ringkasan.transaksi', 0)
            ->assertJsonPath('ringkasan.jumlah', 0)
            ->assertJsonPath('disamarkan', false)
            ->assertJsonPath('pembanding', ['dari' => '2026-09-30', 'sampai' => '2026-09-30', 'ringkasan' => null]);
        $this->report(['bulan' => '2026-09'])
            ->assertJsonPath('ringkasan', null)
            ->assertJsonPath('kendaraanBaru', null)
            ->assertJsonPath('disamarkan', true)
            ->assertJsonPath('pembanding', null)
            ->assertJsonPath('rincian', []);
        $this->report(['bulan' => '2026-10'])
            ->assertJsonPath('ringkasan.transaksi', 3)
            ->assertJsonPath('pembanding', ['dari' => '2026-09-01', 'sampai' => '2026-09-30', 'ringkasan' => null]);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    #[TestWith([['dari' => '2026-10-01', 'sampai' => '2026-10-14'], 'query', 'Parameter kueri tidak didukung.'])]
    #[TestWith([['kecamatan' => ['OEBOBO']], 'query', 'Parameter kueri tidak didukung.'])]
    #[TestWith([['tanggal' => '2026-02-30'], 'tanggal', 'Tanggal laporan tidak valid.'])]
    #[TestWith([['tanggal' => ['2026-10-14']], 'tanggal', 'Tanggal laporan tidak valid.'])]
    #[TestWith([['bulan' => '2026-13'], 'bulan', 'Bulan laporan tidak valid.'])]
    #[TestWith([['bulan' => '2026-10-14'], 'bulan', 'Bulan laporan tidak valid.'])]
    #[TestWith([['tanggal' => '2026-10-14', 'bulan' => '2026-10'], 'tanggal', 'Pilih tanggal atau bulan laporan, tidak keduanya.'])]
    public function test_rejects_an_unknown_parameter_or_an_invalid_period(array $query, string $field, string $message): void
    {
        $this->report($query)->assertUnprocessable()->assertJsonValidationErrors([$field => $message]);
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
     * Every sum of so many default transactions.
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
     * What a group of so many default transactions shows.
     *
     * @return array{transaksi: int, jumlah: int}
     */
    private function figures(int $transactions): array
    {
        return ['transaksi' => $transactions, 'jumlah' => 498000 * $transactions];
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
