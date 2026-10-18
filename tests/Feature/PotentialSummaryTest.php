<?php

namespace Tests\Feature;

use App\Actions\BuildPotentialSummary;
use App\Enums\DashboardModule;
use App\Enums\TaxCompliance;
use App\Models\PotentialImport;
use App\Models\PotentialVehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PotentialSummaryTest extends TestCase
{
    use LazilyRefreshDatabase;

    private PotentialImport $current;

    protected function setUp(): void
    {
        parent::setUp();

        $this->current = PotentialImport::factory()->create(['jumlah' => 6]);
    }

    public function test_answers_null_before_the_first_import(): void
    {
        $this->current->delete();

        $this->summary()->assertOk()->assertExactJson(['impor' => null]);
    }

    public function test_sums_vehicles_and_amounts_for_the_city_each_kecamatan_and_kelurahan(): void
    {
        $this->vehicle();
        $this->vehicle(['kelurahan' => 'LASIANA'], arrears: 1);
        $this->vehicle(['jenis' => 'MINIBUS', 'roda' => 'r4', 'kelurahan' => 'ALAK', 'kecamatan' => 'ALAK', 'pokok_pajak' => 900000], arrears: 4);
        $this->vehicle(['jenis' => 'SPM R3', 'roda' => 'r3', 'kelurahan' => 'ALAK', 'kecamatan' => 'ALAK', 'kepatuhan' => 'Terlambat Membayar 3 Hari', 'kelompok' => TaxCompliance::Terlambat, 'denda_berjalan' => 5000, 'tagihan' => 205000]);
        $this->vehicle(['jenis' => '', 'roda' => null, 'kelurahan' => '', 'kecamatan' => '']);
        PotentialVehicle::factory()->for(PotentialImport::factory()->unfinished(), 'import')->create();

        $response = $this->summary()->assertOk();

        $response->assertJsonPath('impor.id', $this->current->id)
            ->assertJsonPath('bulanDipilih', null)
            ->assertJsonPath('jumlah', [
                'kendaraan' => 5, 'roda' => ['r2' => 2, 'r3' => 1, 'r4' => 1, 'lain' => 1],
                'kelompok' => ['taat' => 2, 'jatuh-tempo' => 0, 'terlambat' => 1, 'menunggak' => 2, 'lainnya' => 0],
                'pokokPajak' => 1700000, 'dendaBerjalan' => 5000, 'pokokTunggak' => 2000000, 'dendaTunggak' => 0, 'tagihan' => 2605000,
            ])
            ->assertJsonPath('kecamatan.*.kunci', ['ALAK', 'KELAPA LIMA', ''])
            ->assertJsonPath('kecamatan.*.nama', ['Alak', 'Kelapa Lima', 'Tanpa kelurahan'])
            ->assertJsonPath('kecamatan.0.kendaraan', 2)
            ->assertJsonPath('kecamatan.0.tagihan', 1405000)
            ->assertJsonPath('kecamatan.1.kelurahan.*.nama', ['LASIANA', 'OESAPA'])
            ->assertJsonPath('kecamatan.1.kelurahan.0.kelompok.menunggak', 1)
            ->assertJsonPath('kecamatan.1.kelurahan.1.kendaraan', 1)
            ->assertJsonPath('kecamatan.2.kelurahan.0.nama', '')
            ->assertJsonPath('tunggakan', [
                ['tahun' => 0, 'kendaraan' => 3, 'pokokTunggak' => 0, 'dendaTunggak' => 0],
                ['tahun' => 1, 'kendaraan' => 1, 'pokokTunggak' => 1000000, 'dendaTunggak' => 0],
                ['tahun' => 4, 'kendaraan' => 1, 'pokokTunggak' => 1000000, 'dendaTunggak' => 0],
            ])
            ->assertJsonPath('jenis', [
                ['jenis' => 'SEPEDA MOTOR', 'roda' => 'r2', 'kendaraan' => 2],
                ['jenis' => '', 'roda' => null, 'kendaraan' => 1],
                ['jenis' => 'MINIBUS', 'roda' => 'r4', 'kendaraan' => 1],
                ['jenis' => 'SPM R3', 'roda' => 'r3', 'kendaraan' => 1],
            ]);
    }

    public function test_groups_the_visit_outcomes_without_the_officers_notes(): void
    {
        $this->vehicle(['dtd_status' => 'SIAP BAYAR | SIAP BAYAR']);
        $this->vehicle(['dtd_status' => 'SIAP BAYAR | SIAP BAYAR MINGGU DEPAN']);
        $this->vehicle(['dtd_status' => 'JUAL BELI | JUAL BELI KEPADA TETANGGA']);
        $this->vehicle();

        $this->summary()->assertJsonPath('kunjungan', [['hasil' => 'SIAP BAYAR', 'kendaraan' => 2], ['hasil' => 'JUAL BELI', 'kendaraan' => 1]]);
    }

    public function test_reads_the_workbooks_visit_outcomes_whatever_their_capitals(): void
    {
        $this->vehicle(['dtd_status' => 'RUSAK BERAT : MESIN TIDAK MENYALA | DI BENGKEL']);
        $this->vehicle(['dtd_status' => 'Rusak Berat : sudah lama']);
        $this->vehicle(['dtd_status' => 'Rusak Berat']);
        $this->vehicle(['dtd_status' => 'PINDAH ALAMAT : KE KOTA LAIN']);

        $this->summary()->assertJsonPath('kunjungan', [['hasil' => 'RUSAK BERAT', 'kendaraan' => 3], ['hasil' => 'PINDAH ALAMAT', 'kendaraan' => 1]]);
    }

    public function test_narrows_to_a_due_month_and_lists_every_month(): void
    {
        $this->vehicle(['bulan_jatuh_tempo' => 3]);
        $this->vehicle(['bulan_jatuh_tempo' => 10]);
        $this->vehicle(['bulan_jatuh_tempo' => 10, 'dtd_status' => 'RUSAK BERAT | RUSAK']);
        $this->vehicle(['bulan_jatuh_tempo' => null]);

        $this->summary(['bulan' => 10])
            ->assertJsonPath('bulanDipilih', 10)
            ->assertJsonPath('jumlah.kendaraan', 2)
            ->assertJsonPath('jenis.0.kendaraan', 2)
            ->assertJsonPath('kunjungan', [['hasil' => 'RUSAK BERAT', 'kendaraan' => 1]])
            ->assertJsonPath('bulan', [['bulan' => 3, 'kendaraan' => 1], ['bulan' => 10, 'kendaraan' => 2]]);
        $this->summary(['bulan' => 13])->assertUnprocessable()->assertJsonValidationErrors(['bulan' => 'Bulan jatuh tempo harus 1 sampai 12.']);
        $this->summary()->assertJsonPath('jumlah.kendaraan', 4);
    }

    public function test_orders_kelurahan_years_and_months_whatever_order_the_counts_were_saved_in(): void
    {
        foreach ([['OESAPA', 10, 2], ['LASIANA', 3, 0], ['OESAPA SELATAN', 7, 1]] as [$kelurahan, $month, $years]) {
            DB::table('potential_summaries')->insert([
                'potential_import_id' => $this->current->id, 'bulan' => $month, 'kecamatan' => 'KELAPA LIMA', 'kelurahan' => $kelurahan, 'roda' => 'r2',
                'kelompok' => 'taat', 'tahun_tunggak' => $years, 'jenis' => 'SEPEDA MOTOR', 'hasil_kunjungan' => '', 'kendaraan' => 1,
                'pokok_pajak' => 0, 'denda_berjalan' => 0, 'pokok_tunggak' => 0, 'denda_tunggak' => 0, 'tagihan' => 0,
            ]);
        }

        $this->actingAs(User::factory()->withModules(DashboardModule::Pemetaan)->create())->getJson('/api/pemetaan/ringkasan')
            ->assertJsonPath('kecamatan.0.kelurahan.*.nama', ['LASIANA', 'OESAPA', 'OESAPA SELATAN'])
            ->assertJsonPath('tunggakan.*.tahun', [0, 1, 2])
            ->assertJsonPath('bulan.*.bulan', [3, 7, 10]);
    }

    /**
     * A vehicle of the current import, in arrears for the given years when
     * given.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function vehicle(array $attributes = [], ?int $arrears = null): void
    {
        $factory = PotentialVehicle::factory()->for($this->current, 'import');

        ($arrears === null ? $factory : $factory->inArrears($arrears))->create($attributes);
    }

    /**
     * The summary of the current import, counted as when it finished.
     *
     * @param  array<string, int>  $filters
     */
    private function summary(array $filters = []): TestResponse
    {
        if ($this->current->exists) {
            app(BuildPotentialSummary::class)->store($this->current);
        }

        return $this->actingAs(User::factory()->withModules(DashboardModule::Pemetaan)->create())->getJson('/api/pemetaan/ringkasan?'.http_build_query($filters));
    }
}
