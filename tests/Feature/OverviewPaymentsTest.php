<?php

namespace Tests\Feature;

use App\Actions\BuildPotentialSummary;
use App\Enums\TaxCompliance;
use App\Models\PotentialImport;
use App\Models\PotentialVehicle;
use App\Models\RevenueImport;
use App\Models\RevenueTransaction;
use App\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Pembayaran PKB on the Ikhtisar page (D-099): each kelurahan's Data
 * Potensi compliance beside the STS transactions paying PKB or arrears from
 * the Data Potensi date through the latest STS date, new vehicles apart.
 */
class OverviewPaymentsTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::query()->forceCreate(['name' => Setting::OVERVIEW, 'value' => ['aktif' => true, 'sejak' => null]]);
    }

    /**
     * @param  array<string, string>  $query
     */
    private function overview(array $query = []): TestResponse
    {
        return $this->getJson('/api/ikhtisar'.($query === [] ? '' : '?'.http_build_query($query)));
    }

    /**
     * Data Potensi set on the date: two vehicles in Oebobo (one paid up, one
     * in arrears), one late in Bakunase II, and one outside the list.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function potential(array $attributes = ['tanggal_penetapan' => '2026-10-16']): void
    {
        $import = PotentialImport::factory()->create(['jumlah' => 4, ...$attributes]);
        $vehicle = fn (string $kelurahan, string $kecamatan) => PotentialVehicle::factory()->for($import, 'import')->state(['kelurahan' => $kelurahan, 'kecamatan' => $kecamatan]);
        $vehicle('OEBOBO', 'OEBOBO')->create();
        $vehicle('OEBOBO', 'OEBOBO')->inArrears(2)->create();
        $vehicle('BAKUNASE II', 'KOTA RAJA')->create(['kelompok' => TaxCompliance::Terlambat, 'kepatuhan' => 'Terlambat Membayar 3 Hari']);
        $vehicle('OESAO', '')->create();
        app(BuildPotentialSummary::class)->store($import);
    }

    /**
     * An STS transaction of the date, paying the given amounts.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, int>  $amounts
     */
    private function payment(string $date, array $attributes = [], array $amounts = ['pkb' => 300000]): void
    {
        RevenueTransaction::factory()->for(RevenueImport::factory(), 'revenueImport')->paying($amounts)->create(['tanggal' => $date, ...$attributes]);
    }

    public function test_counts_the_pkb_payments_since_the_data_potensi_date_beside_its_compliance(): void
    {
        $this->potential();
        $this->payment('2026-10-15', ['kelurahan' => 'Oebobo']);
        $this->payment('2026-10-16', ['kelurahan' => 'Oebobo']);
        $this->payment('2026-11-20', ['kelurahan' => 'Oebobo'], ['tunggakan' => 250000]);
        $this->payment('2026-11-20', ['kelurahan' => 'Oebobo'], ['bbn' => 500000, 'opsen_bbn' => 330000]);
        // The STS kecamatan column does not decide where a kelurahan lies
        $this->payment('2026-11-20', ['kelurahan' => 'BAKUNASE 2', 'kecamatan' => 'OEBOBO']);
        $this->payment('2026-11-20', ['kelurahan' => 'Oesao', 'kecamatan' => 'KUPANG TENGAH']);
        // A village outside the city sharing a city kelurahan's name stays outside; an empty, "-" or lower-case city kecamatan does not
        $this->payment('2026-11-20', ['kelurahan' => 'MERDEKA', 'kecamatan' => 'KUPANG TIMUR']);
        $this->payment('2026-11-20', ['kelurahan' => 'MERDEKA', 'kecamatan' => '-']);
        $this->payment('2026-11-20', ['kelurahan' => 'Oebobo', 'kecamatan' => '']);
        $this->payment('2026-11-20', ['kelurahan' => 'Bakunase', 'kecamatan' => 'kota raja']);
        $this->payment('2026-11-20', ['kelurahan' => 'Oebobo', 'jenis_daftar' => 'Baru']);
        // A smaller jenis daftar that the database returns first: the list is still largest first
        $this->payment('2026-11-20', ['kelurahan' => 'Alak', 'kecamatan' => 'ALAK', 'jenis_daftar' => 'Balik Nama']);

        $response = $this->overview()->assertOk()
            ->assertJsonPath('pembayaran.dataPotensi', ['tanggal' => '2026-10-16'])
            ->assertJsonPath('pembayaran.sts', ['dari' => '2026-10-16', 'sampai' => '2026-11-20'])
            ->assertJsonPath('pembayaran.kecamatan.*.kunci', ['ALAK', 'KELAPA LIMA', 'KOTA LAMA', 'KOTA RAJA', 'MAULAFA', 'OEBOBO'])
            ->assertJsonPath('pembayaran.kecamatan.5.nama', 'Oebobo')
            ->assertJsonPath('pembayaran.kecamatan.5.kendaraan', 2)
            ->assertJsonPath('pembayaran.kecamatan.5.taat', 1)
            ->assertJsonPath('pembayaran.kecamatan.5.menunggak', 1)
            ->assertJsonPath('pembayaran.kecamatan.5.bayar', 3)
            ->assertJsonPath('pembayaran.kecamatan.3.bayar', 2)
            ->assertJsonPath('pembayaran.kecamatan.2.bayar', 1)
            ->assertJsonPath('pembayaran.kecamatan.3.belumBayar', 1)
            ->assertJsonPath('pembayaran.lainnya', ['kendaraan' => 1, 'taat' => 1, 'belumBayar' => 0, 'menunggak' => 0, 'bayar' => 2])
            ->assertJsonPath('pembayaran.baru', 1)
            ->assertJsonPath('pembayaran.kecamatan.0.bayar', 1)
            ->assertJsonPath('pembayaran.jumlah', ['kendaraan' => 4, 'taat' => 2, 'belumBayar' => 1, 'menunggak' => 1, 'bayar' => 9])
            ->assertJsonPath('pembayaran.jenisDaftar', [
                ['nilai' => 'Teliti Ulang / Pengesahan', 'transaksi' => 8], ['nilai' => 'Balik Nama', 'transaksi' => 1], ['nilai' => 'Baru', 'transaksi' => 1],
            ]);

        $oebobo = collect($response->json('pembayaran.kecamatan.5.kelurahan'))->firstWhere('nama', 'OEBOBO');
        $this->assertSame(['nama' => 'OEBOBO', 'kendaraan' => 2, 'taat' => 1, 'belumBayar' => 0, 'menunggak' => 1, 'bayar' => 3], $oebobo);
        $this->assertSame(['nama' => 'BAKUNASE II', 'kendaraan' => 1, 'taat' => 0, 'belumBayar' => 1, 'menunggak' => 0, 'bayar' => 1],
            collect($response->json('pembayaran.kecamatan.3.kelurahan'))->firstWhere('nama', 'BAKUNASE II'));
        // Every kelurahan of the list is shown, also without a figure, and the totals reconcile with Pemetaan PKB
        $this->assertCount(51, Arr::flatten(array_column($response->json('pembayaran.kecamatan'), 'kelurahan'), 1));
        $this->assertSame($response->json('pemetaan.jumlah.kendaraan'), $response->json('pembayaran.jumlah.kendaraan'));
    }

    public function test_counts_through_the_latest_sts_date_whatever_the_period_and_filters(): void
    {
        $this->potential();
        $this->payment('2026-10-20', ['kelurahan' => 'Oebobo']);
        $this->payment('2026-11-20', ['kelurahan' => 'Oebobo', 'loket' => 'keliling-01']);

        $this->overview(['dari' => '2026-11-20', 'sampai' => '2026-11-20'])->assertOk()->assertJsonPath('pembayaran.jumlah.bayar', 2);
        $this->overview(['dari' => '2026-10-01', 'sampai' => '2026-10-10'])->assertOk()->assertJsonPath('pembayaran.jumlah.bayar', 2);
        $this->getJson('/api/ikhtisar?loket[]=keliling-01&kelurahan[]=ALAK')->assertOk()
            ->assertJsonPath('penerimaan.ringkasan.transaksi', 0)
            ->assertJsonPath('pembayaran.jumlah.bayar', 2);
    }

    public function test_starts_on_the_wita_day_the_import_finished_when_the_data_potensi_has_no_date(): void
    {
        $this->potential(['tanggal_penetapan' => null, 'selesai_pada' => '2026-10-16 17:00:00']);
        $this->payment('2026-10-16', ['kelurahan' => 'Oebobo']);
        $this->payment('2026-10-17', ['kelurahan' => 'Oebobo']);

        $this->overview()->assertOk()
            ->assertJsonPath('pembayaran.sts.dari', '2026-10-17')
            ->assertJsonPath('pembayaran.jumlah.bayar', 1);
    }

    public function test_counts_nothing_before_the_data_potensi_or_without_sts(): void
    {
        $this->potential(['tanggal_penetapan' => '2026-11-21']);

        $this->overview()->assertOk()
            ->assertJsonPath('pembayaran.sts', null)
            ->assertJsonPath('pembayaran.jumlah.kendaraan', 4)
            ->assertJsonPath('pembayaran.jumlah.bayar', 0)
            ->assertJsonPath('pembayaran.jenisDaftar', []);

        $this->payment('2026-11-20', ['kelurahan' => 'Oebobo']);
        $this->overview()->assertOk()
            ->assertJsonPath('pembayaran.sts', null)
            ->assertJsonPath('pembayaran.jumlah.bayar', 0)
            ->assertJsonPath('pembayaran.baru', 0);
    }

    public function test_answers_null_without_data_potensi(): void
    {
        $this->payment('2026-11-20', ['kelurahan' => 'Oebobo']);

        $this->overview()->assertOk()->assertJsonPath('pembayaran', null);
    }
}
