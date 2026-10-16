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
                'kendaraan' => 10, 'rodaDua' => 7, 'rodaTiga' => 0, 'rodaEmpat' => 3, 'taat' => 7, 'belumBayar' => 0, 'menunggak' => 3,
                'potensi' => 4100000, 'tagihan' => 3900000, 'tunggakan' => 3300000,
                'kelurahan' => [
                    ['nama' => 'LASIANA', 'kendaraan' => 3, 'rodaDua' => 3, 'rodaTiga' => 0, 'rodaEmpat' => 0, 'taat' => 0, 'belumBayar' => 0, 'menunggak' => 3, 'potensi' => 600000, 'tagihan' => 3900000, 'tunggakan' => 3300000],
                    ['nama' => 'OESAPA', 'kendaraan' => 7, 'rodaDua' => 4, 'rodaTiga' => 0, 'rodaEmpat' => 3, 'taat' => 7, 'belumBayar' => 0, 'menunggak' => 0, 'potensi' => 3500000, 'tagihan' => 0, 'tunggakan' => 0],
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
            'nama' => 'OESAPA', 'kendaraan' => 7, 'rodaDua' => null, 'rodaTiga' => 0, 'rodaEmpat' => null, 'taat' => null, 'belumBayar' => 0, 'menunggak' => null,
            'potensi' => 1400000, 'tagihan' => null, 'tunggakan' => null,
        ]);
        // Lasiana: one three-wheeler; the vehicles of no recorded type do not hide it, as other figures may give their number away
        $response->assertJsonPath('kota.rodaLain', 5)
            ->assertJsonPath('kecamatan.0.kelurahan.0.kendaraan', 11)
            ->assertJsonPath('kecamatan.0.kelurahan.0.rodaDua', null)
            ->assertJsonPath('kecamatan.0.kelurahan.0.rodaTiga', null)
            ->assertJsonPath('kecamatan.0.kelurahan.0.rodaEmpat', 0)
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

    public function test_hides_a_single_three_wheeler_with_the_four_wheelers_of_its_areas(): void
    {
        $this->vehicles(10, 'OESAPA');
        $this->vehicles(1, 'OESAPA', ['jenis' => 'SPM R3', 'roda' => 'r3']);
        $this->vehicles(4, 'OESAPA', ['jenis' => 'MINIBUS', 'roda' => 'r4']);

        // The vehicles less the motorcycles would give the one three-wheeler away with the four-wheelers shown
        $this->map()->assertJsonPath('kota.rodaDua', 10)
            ->assertJsonPath('kota.rodaTiga', null)
            ->assertJsonPath('kota.rodaEmpat', null)
            ->assertJsonPath('kecamatan.0.rodaTiga', null)
            ->assertJsonPath('kecamatan.0.rodaEmpat', null)
            ->assertJsonPath('kecamatan.0.kelurahan.0.rodaEmpat', null);
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
            ->assertJsonPath('kecamatan.0.kelurahan.0.rodaTiga', 0)
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

    public function test_hides_the_four_wheelers_of_a_kelurahan_whose_motorcycles_are_hidden_for_its_kecamatan(): void
    {
        $this->vehicles(10, 'LASIANA');
        $this->vehicles(1, 'LASIANA', ['jenis' => 'SPM R3', 'roda' => 'r3']);
        $this->vehicles(20, 'OESAPA');
        $this->vehicles(5, 'OESAPA', ['jenis' => 'MINIBUS', 'roda' => 'r4']);
        $this->vehicles(30, 'OESAPA SELATAN');
        $this->vehicles(10, 'NAIONI', ['kecamatan' => 'ALAK']);
        $this->vehicles(10, 'NAIONI', ['kecamatan' => 'ALAK', 'jenis' => 'MINIBUS', 'roda' => 'r4']);
        $this->vehicles(10, 'ALAK', ['kecamatan' => 'ALAK']);
        $this->vehicles(10, 'ALAK', ['kecamatan' => 'ALAK', 'jenis' => 'MINIBUS', 'roda' => 'r4']);

        // Oesapa's motorcycles are hidden for Kelapa Lima's motorcycle total; its four-wheelers must not leave them alone in its row
        $this->map()->assertJsonPath('kecamatan.1.kelurahan.1.nama', 'OESAPA')
            ->assertJsonPath('kecamatan.1.kelurahan.1.rodaDua', null)
            ->assertJsonPath('kecamatan.1.kelurahan.1.rodaEmpat', null);
    }

    public function test_settles_every_total_again_after_hiding_a_part_for_another(): void
    {
        // Wheel class and compliance group of the vehicles of three kelurahan of Alak (found by a randomised check)
        $kelurahan = [
            'ALAK' => [['r2', 'jatuh-tempo', 1], ['r2', 'terlambat', 1], ['r2', 'menunggak', 1], ['r3', 'taat', 1], ['r3', 'terlambat', 2], ['r3', 'menunggak', 1], ['r4', 'taat', 1], ['r4', 'jatuh-tempo', 14], ['r4', 'terlambat', 1], ['r4', 'menunggak', 2], ['lain', 'jatuh-tempo', 3]],
            'NAIONI' => [['r2', 'jatuh-tempo', 1], ['r2', 'lainnya', 1], ['r3', 'taat', 2], ['r3', 'jatuh-tempo', 5], ['r3', 'terlambat', 2], ['r4', 'taat', 2], ['r4', 'jatuh-tempo', 2], ['r4', 'terlambat', 2], ['lain', 'taat', 2], ['lain', 'lainnya', 2]],
            'NAMOSAIN' => [['r2', 'menunggak', 1], ['r3', 'taat', 1], ['r3', 'menunggak', 4], ['r4', 'jatuh-tempo', 2], ['r4', 'terlambat', 1], ['lain', 'taat', 1], ['lain', 'terlambat', 2], ['lain', 'menunggak', 1]],
        ];
        $wheels = ['r2' => ['jenis' => 'SEPEDA MOTOR', 'roda' => 'r2'], 'r3' => ['jenis' => 'SPM R3', 'roda' => 'r3'], 'r4' => ['jenis' => 'MINIBUS', 'roda' => 'r4'], 'lain' => ['jenis' => '', 'roda' => null]];
        $groups = [
            'taat' => [],
            'jatuh-tempo' => ['kepatuhan' => 'Pajak Berlaku Sampai 20/10/2026', 'kelompok' => TaxCompliance::JatuhTempo],
            'terlambat' => ['kepatuhan' => 'Terlambat Membayar 3 Hari', 'kelompok' => TaxCompliance::Terlambat],
            'lainnya' => ['kepatuhan' => 'Blokir', 'kelompok' => TaxCompliance::Lainnya],
        ];
        foreach ($kelurahan as $nama => $kelompok) {
            foreach ($kelompok as [$roda, $kepatuhan, $jumlah]) {
                $this->vehicles($jumlah, $nama, ['kecamatan' => 'ALAK', ...$wheels[$roda], ...($groups[$kepatuhan] ?? [])], $kepatuhan === 'menunggak' ? fn ($factory) => $factory->inArrears(1) : null);
            }
        }

        // map() checks that no total is left with a single hidden figure
        $this->map()->assertJsonPath('kecamatan.0.kelurahan.*.nama', ['ALAK', 'NAIONI', 'NAMOSAIN']);
    }

    /**
     * No total and its parts hold exactly one hidden figure, which the others
     * would work out: an area's vehicles over its wheel classes and its
     * compliance groups, and the city and kecamatan figures over the areas
     * within.
     */
    private function assertNothingHiddenAlone(TestResponse $response): void
    {
        $map = $response->json();
        $areas = [$map['kota']];
        $relations = [];
        foreach (['kendaraan', 'rodaDua', 'rodaTiga', 'rodaEmpat', 'taat', 'belumBayar', 'menunggak', 'potensi', 'tagihan', 'tunggakan'] as $column) {
            $relations[] = [$map['kota'][$column], array_column($map['kecamatan'], $column)];
            foreach ($map['kecamatan'] as $kecamatan) {
                if ($kecamatan['kelurahan'] !== []) {
                    $relations[] = [$kecamatan[$column], array_column($kecamatan['kelurahan'], $column)];
                }
            }
        }
        foreach ($map['kecamatan'] as $kecamatan) {
            $areas = [...$areas, $kecamatan, ...$kecamatan['kelurahan']];
        }
        foreach ($areas as $area) {
            $relations[] = [$area['kendaraan'], [$area['rodaDua'], $area['rodaTiga'], $area['rodaEmpat']]];
            $relations[] = [$area['kendaraan'], [$area['taat'], $area['belumBayar'], $area['menunggak']]];
        }

        foreach ($relations as [$total, $parts]) {
            $this->assertNotSame(1, count(array_filter([$total, ...$parts], fn (?int $value): bool => $value === null)));
        }
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

    /**
     * The public map, which never leaves a hidden figure alone in a total.
     */
    private function map(): TestResponse
    {
        if ($this->current->exists) {
            app(BuildPotentialSummary::class)->store($this->current);
        }

        $response = $this->getJson('/api/public/pemetaan');
        if ($response->json('tersedia')) {
            $this->assertNothingHiddenAlone($response);
        }

        return $response;
    }
}
