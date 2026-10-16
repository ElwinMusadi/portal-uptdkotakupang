<?php

namespace Tests\Feature;

use App\Actions\BuildPotentialSummary;
use App\Enums\TaxCompliance;
use App\Models\PotentialImport;
use App\Models\PotentialVehicle;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicPotentialMapTest extends TestCase
{
    use LazilyRefreshDatabase;

    private PotentialImport $current;

    protected function setUp(): void
    {
        parent::setUp();

        $this->current = PotentialImport::factory()->create(['tampil' => true]);
    }

    public function test_tells_nothing_before_an_administrator_shows_the_data(): void
    {
        $this->vehicles(5, 'OESAPA');
        $this->current->update(['tampil' => false]);

        $this->map()->assertOk()->assertExactJson(['tersedia' => false]);

        $this->current->delete();
        $this->map()->assertOk()->assertExactJson(['tersedia' => false]);
    }

    public function test_gives_the_city_kecamatan_and_kelurahan_figures_without_identities(): void
    {
        $this->vehicles(4, 'OESAPA');
        $this->vehicles(3, 'OESAPA', ['jenis' => 'MINIBUS', 'roda' => 'r4', 'pokok_pajak' => 900000]);
        $this->vehicles(3, 'LASIANA', ['denda_tunggak' => 100000, 'tagihan' => 1300000], fn ($factory) => $factory->inArrears(2));
        $this->vehicles(2, 'ALAK', ['kecamatan' => 'ALAK', 'kepatuhan' => 'Terlambat Membayar 3 Hari', 'kelompok' => TaxCompliance::Terlambat, 'tagihan' => 210000]);
        $this->vehicles(1, 'ALAK', ['kecamatan' => 'ALAK', 'kepatuhan' => 'Pajak Berlaku Sampai 20/10/2026', 'kelompok' => TaxCompliance::JatuhTempo, 'tagihan' => 210000]);
        $this->vehicles(3, '', ['kecamatan' => '', 'jenis' => 'SPM R3', 'roda' => 'r3']);

        $response = $this->map()->assertOk();

        $response->assertJsonPath('tersedia', true)
            ->assertJsonPath('tanggalPenetapan', '2026-10-16')
            ->assertJsonPath('diperbarui', '2026-10-16T02:00:00+00:00')
            ->assertJsonPath('minimum', 3)
            ->assertJsonPath('kota', [
                'kendaraan' => 16, 'rodaDua' => 10, 'rodaTiga' => 3, 'rodaEmpat' => 3, 'rodaLain' => 0, 'taat' => 10, 'belumBayar' => 3, 'menunggak' => 3,
                'potensi' => 5300000, 'tagihan' => 4530000, 'tunggakan' => 3300000,
            ])
            ->assertJsonPath('kecamatan.*.kunci', ['ALAK', 'KELAPA LIMA', ''])
            ->assertJsonPath('kecamatan.1', [
                'kunci' => 'KELAPA LIMA', 'nama' => 'Kelapa Lima',
                'kendaraan' => 10, 'rodaDua' => 7, 'rodaTigaLebih' => 3, 'taat' => 7, 'belumBayar' => 0, 'menunggak' => 3,
                'potensi' => 4100000, 'tagihan' => 3900000, 'tunggakan' => 3300000,
                'kelurahan' => [
                    ['nama' => 'LASIANA', 'kendaraan' => 3, 'rodaDua' => 3, 'rodaTigaLebih' => 0, 'taat' => 0, 'belumBayar' => 0, 'menunggak' => 3, 'potensi' => 600000, 'tagihan' => 3900000, 'tunggakan' => 3300000],
                    ['nama' => 'OESAPA', 'kendaraan' => 7, 'rodaDua' => 4, 'rodaTigaLebih' => 3, 'taat' => 7, 'belumBayar' => 0, 'menunggak' => 0, 'potensi' => 3500000, 'tagihan' => 0, 'tunggakan' => 0],
                ],
            ])
            ->assertJsonPath('kecamatan.2.nama', 'Tanpa kelurahan')
            ->assertJsonPath('kecamatan.2.kelurahan', []);
        $this->assertStringNotContainsString('Pemilik', $response->getContent());
        $this->assertStringNotContainsString('DH', $response->getContent());
        $this->assertSame([], $response->headers->getCookies());
        $response->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_hides_figures_below_three_vehicles_and_what_would_give_them_away_in_the_area(): void
    {
        $this->vehicles(6, 'OESAPA');
        $this->vehicles(1, 'OESAPA', ['jenis' => 'MINIBUS', 'roda' => 'r4'], fn ($factory) => $factory->inArrears(1));
        $this->vehicles(5, 'LASIANA');
        $this->vehicles(1, 'LASIANA', ['jenis' => 'SPM R3', 'roda' => 'r3']);
        $this->vehicles(5, 'LASIANA', ['jenis' => '', 'roda' => null]);

        $response = $this->map();

        // Oesapa: one car in arrears; the motorcycles and paid-up count would give it away
        $response->assertJsonPath('kecamatan.0.kelurahan.1', [
            'nama' => 'OESAPA', 'kendaraan' => 7, 'rodaDua' => null, 'rodaTigaLebih' => null, 'taat' => null, 'belumBayar' => 0, 'menunggak' => null,
            'potensi' => 1400000, 'tagihan' => null, 'tunggakan' => null,
        ]);
        // Lasiana: one three-wheeler; the vehicles of no recorded type do not hide it, as other figures may give their number away
        $response->assertJsonPath('kota.rodaLain', 5)
            ->assertJsonPath('kecamatan.0.kelurahan.0.kendaraan', 11)
            ->assertJsonPath('kecamatan.0.kelurahan.0.rodaDua', null)
            ->assertJsonPath('kecamatan.0.kelurahan.0.rodaTigaLebih', null)
            ->assertJsonPath('kecamatan.0.kelurahan.0.taat', 11);
    }

    /**
     * @return array<string, array{int, int, int}>
     */
    public static function complianceGroups(): array
    {
        return [
            'the smallest other figure first' => [10, 4, 1],
            'until the hidden ones make three' => [10, 2, 1],
            'two vehicles as well' => [10, 2, 5],
        ];
    }

    #[DataProvider('complianceGroups')]
    public function test_hides_the_smallest_other_figures_of_a_total_with_one_below_three_vehicles(int $paidUp, int $late, int $inArrears): void
    {
        $this->vehicles($paidUp, 'OESAPA');
        $this->vehicles($late, 'OESAPA', ['kepatuhan' => 'Terlambat Membayar 3 Hari', 'kelompok' => TaxCompliance::Terlambat]);
        $this->vehicles($inArrears, 'OESAPA', state: fn ($factory) => $factory->inArrears(1));

        $this->map()->assertJsonPath('kecamatan.0.kelurahan.0.taat', 10)
            ->assertJsonPath('kecamatan.0.kelurahan.0.belumBayar', null)
            ->assertJsonPath('kecamatan.0.kelurahan.0.menunggak', null)
            ->assertJsonPath('kota.taat', 10);
    }

    public function test_hides_the_city_four_wheelers_that_would_give_a_single_three_wheeler_away(): void
    {
        $this->vehicles(10, 'OESAPA');
        $this->vehicles(1, 'OESAPA', ['jenis' => 'SPM R3', 'roda' => 'r3']);
        $this->vehicles(4, 'OESAPA', ['jenis' => 'MINIBUS', 'roda' => 'r4']);

        // The kecamatan give the three-wheelers and up together, so the city's four-wheelers would give the one three-wheeler away
        $this->map()->assertJsonPath('kota.rodaDua', 10)
            ->assertJsonPath('kota.rodaTiga', null)
            ->assertJsonPath('kota.rodaEmpat', null)
            ->assertJsonPath('kecamatan.0.rodaTigaLebih', 5);
    }

    public function test_hides_the_city_vehicles_of_no_recorded_type_only_below_three(): void
    {
        $this->vehicles(10, 'OESAPA');
        $this->vehicles(2, 'OESAPA', ['jenis' => '', 'roda' => null]);

        // They are the city's vehicles less its wheel classes anyway, so nothing else is hidden for them
        $this->map()->assertJsonPath('kota.rodaLain', null)
            ->assertJsonPath('kota.kendaraan', 12)
            ->assertJsonPath('kota.rodaDua', 10)
            ->assertJsonPath('kecamatan.0.kelurahan.0.rodaDua', 10)
            ->assertJsonMissingPath('kecamatan.0.rodaLain');
    }

    public function test_hides_a_kelurahan_figure_that_the_kecamatan_total_would_give_away(): void
    {
        $this->vehicles(1, 'OESAPA', state: fn ($factory) => $factory->inArrears(3));
        $this->vehicles(10, 'OESAPA');
        $this->vehicles(10, 'LASIANA', state: fn ($factory) => $factory->inArrears(3, owed: 500000));
        $this->vehicles(4, 'LASIANA');
        $this->vehicles(20, 'OESAPA SELATAN', state: fn ($factory) => $factory->inArrears(3, owed: 400000));

        $response = $this->map();

        // Oesapa's single car in arrears is hidden, with Lasiana's (the smallest shown) since Kelapa Lima's total is published
        $response->assertJsonPath('kecamatan.0.menunggak', 31)
            ->assertJsonPath('kecamatan.0.kelurahan.*.nama', ['LASIANA', 'OESAPA', 'OESAPA SELATAN'])
            ->assertJsonPath('kecamatan.0.kelurahan.*.menunggak', [null, null, 20])
            ->assertJsonPath('kecamatan.0.kelurahan.*.tunggakan', [null, null, 8000000]);
    }

    public function test_hides_more_until_the_hidden_figures_describe_three_vehicles_together(): void
    {
        $this->vehicles(1, 'OESAPA', state: fn ($factory) => $factory->inArrears(1));
        $this->vehicles(10, 'OESAPA');
        $this->vehicles(1, 'LASIANA', state: fn ($factory) => $factory->inArrears(1));
        $this->vehicles(10, 'LASIANA');
        $this->vehicles(10, 'OESAPA SELATAN', state: fn ($factory) => $factory->inArrears(1));

        // Two cars in arrears hidden apart would still add up to two from Kelapa Lima's total
        $this->map()->assertJsonPath('kecamatan.0.kelurahan.*.nama', ['LASIANA', 'OESAPA', 'OESAPA SELATAN'])
            ->assertJsonPath('kecamatan.0.kelurahan.*.menunggak', [null, null, null])
            ->assertJsonPath('kecamatan.0.kelurahan.*.tunggakan', [null, null, null])
            ->assertJsonPath('kecamatan.0.menunggak', 12);
    }

    public function test_hides_again_where_a_figure_hidden_for_another_total_gives_one_away(): void
    {
        $this->vehicles(1, 'OESAPA', state: fn ($factory) => $factory->inArrears(1));
        $this->vehicles(10, 'OESAPA');
        $this->vehicles(4, 'LASIANA');
        $this->vehicles(20, 'LASIANA', state: fn ($factory) => $factory->inArrears(1));
        $this->vehicles(10, 'OESAPA SELATAN', state: fn ($factory) => $factory->inArrears(1));

        // Lasiana's paid-up cars are hidden for Kelapa Lima's paid-up total; shown, its cars in arrears would give them away
        $this->map()->assertJsonPath('kecamatan.0.kelurahan.0.taat', null)
            ->assertJsonPath('kecamatan.0.kelurahan.0.menunggak', null);
    }

    public function test_hides_more_under_a_hidden_total_that_the_city_total_gives_away(): void
    {
        $this->vehicles(1, 'NAIONI', ['kecamatan' => 'ALAK'], fn ($factory) => $factory->inArrears(1));
        $this->vehicles(10, 'NAIONI', ['kecamatan' => 'ALAK']);
        $this->vehicles(1, 'OESAPA', state: fn ($factory) => $factory->inArrears(1));
        $this->vehicles(10, 'OESAPA');
        $this->vehicles(4, 'LASIANA', state: fn ($factory) => $factory->inArrears(1));
        $this->vehicles(10, 'LASIANA');

        // Kelapa Lima's cars in arrears are hidden for Alak's, but the city total minus Lasiana's
        // would still give Naioni's and Oesapa's together as two
        $this->map()->assertJsonPath('kota.menunggak', 6)
            ->assertJsonPath('kecamatan.1.menunggak', null)
            ->assertJsonPath('kecamatan.1.kelurahan.*.menunggak', [null, null]);
    }

    public function test_hides_one_more_where_all_totals_combined_give_two_single_cars_away(): void
    {
        $late = ['kepatuhan' => 'Terlambat Membayar 3 Hari', 'kelompok' => TaxCompliance::Terlambat];
        $this->vehicles(25, 'LASIANA');
        $this->vehicles(1, 'LASIANA', $late);
        $this->vehicles(5, 'LASIANA', state: fn ($factory) => $factory->inArrears(1));
        $this->vehicles(1, 'OESAPA');
        $this->vehicles(20, 'OESAPA', $late);
        $this->vehicles(6, 'OESAPA', state: fn ($factory) => $factory->inArrears(1));
        $this->vehicles(4, 'OESAPA SELATAN');
        $this->vehicles(7, 'OESAPA SELATAN', $late);
        $this->vehicles(20, 'OESAPA SELATAN', state: fn ($factory) => $factory->inArrears(1));

        // Each total on its own is safe, but all combined would give Lasiana's late car and Oesapa's paid-up car away
        // together; Oesapa's late cars are hidden too, the fewest that stop it
        $this->map()->assertJsonPath('kecamatan.0.kelurahan.*.taat', [25, null, null])
            ->assertJsonPath('kecamatan.0.kelurahan.*.belumBayar', [null, null, null])
            ->assertJsonPath('kecamatan.0.kelurahan.*.menunggak', [null, null, 20]);
    }

    public function test_hides_a_zero_where_the_vehicles_of_other_compliance_texts_give_a_total_away(): void
    {
        $this->vehicles(1, 'LASIANA', ['kepatuhan' => 'Blokir', 'kelompok' => TaxCompliance::Lainnya]);
        $this->vehicles(20, 'OESAPA');

        // Shown as zeros, Lasiana's paid-up, late and in-arrears figures would give away its one vehicle with another compliance text
        $this->map()->assertJsonPath('kecamatan.0.kelurahan.0.nama', 'LASIANA')
            ->assertJsonPath('kecamatan.0.kelurahan.0.kendaraan', null)
            ->assertJsonPath('kecamatan.0.kelurahan.0.rodaTigaLebih', 0)
            ->assertJsonPath('kecamatan.0.kelurahan.0.taat', null)
            ->assertJsonPath('kecamatan.0.kelurahan.0.belumBayar', 0)
            ->assertJsonPath('kecamatan.0.kelurahan.0.menunggak', 0);
    }

    public function test_hides_a_total_when_no_other_figure_can_hide_its_part(): void
    {
        $this->vehicles(1, 'NAIONI', ['kecamatan' => 'ALAK'], fn ($factory) => $factory->inArrears(1));
        $this->vehicles(9, 'NAIONI', ['kecamatan' => 'ALAK']);
        $this->vehicles(9, 'OESAPA', state: fn ($factory) => $factory->inArrears(1));

        $response = $this->map();

        // Naioni's paid-up cars hide its one car in arrears; with no paid-up car in Kelapa Lima
        // to hide alongside Alak's, the city's paid-up total is hidden, and its arrears with it
        $response->assertJsonPath('kecamatan.0.kelurahan.0.menunggak', null)
            ->assertJsonPath('kecamatan.0.kelurahan.0.taat', null)
            ->assertJsonPath('kecamatan.0.taat', null)
            ->assertJsonPath('kecamatan.1.taat', 0)
            ->assertJsonPath('kecamatan.1.menunggak', null)
            ->assertJsonPath('kota.kendaraan', 19)
            ->assertJsonPath('kota.taat', null)
            ->assertJsonPath('kota.menunggak', null);
    }

    public function test_keeps_vehicles_outside_the_kelurahan_list_hidden_among_more(): void
    {
        $this->vehicles(1, 'BAUMATA', ['kecamatan' => '']);
        $this->vehicles(10, 'OESAPA');

        $this->map()->assertJsonPath('kecamatan.*.kunci', ['KELAPA LIMA', ''])
            ->assertJsonPath('kecamatan.1.kendaraan', null)
            ->assertJsonPath('kecamatan.0.kendaraan', null)
            ->assertJsonPath('kota.kendaraan', 11);
    }

    public function test_rejects_query_parameters(): void
    {
        $this->getJson('/api/public/pemetaan?kecamatan=ALAK')->assertUnprocessable()->assertJsonValidationErrors(['query' => 'Parameter kueri tidak didukung.']);
    }

    /**
     * Vehicles of the current import in a kelurahan of Kelapa Lima unless
     * told otherwise, paid-up motorcycles unless changed.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function vehicles(int $count, string $kelurahan, array $attributes = [], ?callable $state = null): void
    {
        $factory = PotentialVehicle::factory()->count($count)->for($this->current, 'import');

        ($state === null ? $factory : $state($factory))->create(['kelurahan' => $kelurahan, ...$attributes]);
    }

    private function map(): TestResponse
    {
        if ($this->current->exists) {
            app(BuildPotentialSummary::class)->store($this->current);
        }

        return $this->getJson('/api/public/pemetaan');
    }
}
