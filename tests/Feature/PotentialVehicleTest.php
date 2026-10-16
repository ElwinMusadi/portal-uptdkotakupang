<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Enums\TaxCompliance;
use App\Http\Controllers\Dashboard\PotentialVehicleController;
use App\Models\PotentialImport;
use App\Models\PotentialVehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PotentialVehicleTest extends TestCase
{
    use LazilyRefreshDatabase;

    private PotentialImport $current;

    protected function setUp(): void
    {
        parent::setUp();

        $this->current = PotentialImport::factory()->create();
    }

    public function test_lists_the_vehicles_with_the_most_to_pay_first_with_their_owner(): void
    {
        $paidFirst = $this->vehicle(['urut' => 1]);
        $most = $this->vehicle(['urut' => 4, 'nopol' => 'DH5555XY', 'nama' => 'Pemilik Tunggakan', 'tagihan' => 900000]);
        $less = $this->vehicle(['urut' => 3, 'tagihan' => 300000]);
        $paid = $this->vehicle(['urut' => 2]);
        PotentialVehicle::factory()->for(PotentialImport::factory()->unfinished(), 'import')->create(['tagihan' => 99000000]);

        $this->list()->assertOk()
            ->assertJsonPath('jumlah', 4)
            ->assertJsonPath('kendaraan.*.id', [$most->id, $less->id, $paidFirst->id, $paid->id])
            ->assertJsonPath('kendaraan.0.nopol', 'DH5555XY')
            ->assertJsonPath('kendaraan.0.nama', 'Pemilik Tunggakan')
            ->assertJsonPath('kendaraan.0.telepon', '081200000001')
            ->assertJsonPath('kendaraan.0.kelompok', 'taat')
            ->assertJsonMissingPath('kendaraan.0.ktp');
    }

    public function test_narrows_by_text_place_group_wheels_arrears_and_due_month(): void
    {
        $byPlate = $this->vehicle(['nopol' => 'DH7777QQ']);
        $byName = $this->vehicle(['nama' => 'Yohanes Banunaek']);
        $alak = $this->vehicle(['kecamatan' => 'ALAK', 'kelurahan' => 'NAIONI']);
        $unplaced = $this->vehicle(['kecamatan' => '', 'kelurahan' => '']);
        $late = $this->vehicle(['kelompok' => TaxCompliance::Terlambat]);
        $car = $this->vehicle(['roda' => 'r4']);
        $fourYears = PotentialVehicle::factory()->for($this->current, 'import')->inArrears(4)->create();
        $march = $this->vehicle(['bulan_jatuh_tempo' => 3]);

        $this->list(['q' => 'dh 7777'])->assertJsonPath('kendaraan.*.id', [$byPlate->id]);
        $this->list(['q' => 'banunaek'])->assertJsonPath('kendaraan.*.id', [$byName->id]);
        $this->list(['kecamatan' => 'ALAK'])->assertJsonPath('kendaraan.*.id', [$alak->id]);
        $this->list(['kecamatan' => 'tanpa'])->assertJsonPath('kendaraan.*.id', [$unplaced->id]);
        $this->list(['kelurahan' => 'NAIONI'])->assertJsonPath('kendaraan.*.id', [$alak->id]);
        $this->list(['kelompok' => 'terlambat'])->assertJsonPath('kendaraan.*.id', [$late->id]);
        $this->list(['roda' => 'r4'])->assertJsonPath('kendaraan.*.id', [$car->id]);
        $this->list(['tahun' => 4])->assertJsonPath('kendaraan.*.id', [$fourYears->id])->assertJsonPath('jumlah', 1);
        $this->list(['tahun' => 0])->assertJsonPath('jumlah', 7);
        $this->list(['bulan' => 3])->assertJsonPath('kendaraan.*.id', [$march->id]);
    }

    public function test_narrows_to_every_vehicle_that_still_owes_tax(): void
    {
        $this->vehicle(['tagihan' => 0]);
        $due = $this->vehicle(['kelompok' => TaxCompliance::JatuhTempo, 'tagihan' => 300000]);
        $late = $this->vehicle(['kelompok' => TaxCompliance::Terlambat, 'tagihan' => 400000]);
        $arrears = PotentialVehicle::factory()->for($this->current, 'import')->inArrears(2)->create();
        $arrearsWithoutAmount = PotentialVehicle::factory()->for($this->current, 'import')->inArrears(4, owed: 0)->create(['tagihan' => 0]);
        $other = $this->vehicle(['kelompok' => TaxCompliance::Lainnya, 'tagihan' => 100000]);

        $this->list(['kelompok' => 'belum-taat'])
            ->assertJsonPath('jumlah', 5)
            ->assertJsonPath('kendaraan.*.id', [$arrears->id, $late->id, $due->id, $other->id, $arrearsWithoutAmount->id]);
    }

    public function test_matches_the_wildcards_typed_in_the_search_as_themselves(): void
    {
        $percent = $this->vehicle(['nama' => 'Toko 100% Jaya']);
        $this->vehicle(['nama' => 'Toko 1000 Jaya']);
        $underscore = $this->vehicle(['nama' => 'Ina_Lado']);
        $this->vehicle(['nama' => 'InaXLado']);
        $exclamation = $this->vehicle(['nama' => 'Seru!Bersama']);

        $this->list(['q' => '100%'])->assertJsonPath('kendaraan.*.id', [$percent->id]);
        $this->list(['q' => 'a_l'])->assertJsonPath('kendaraan.*.id', [$underscore->id]);
        $this->list(['q' => 'u!b'])->assertJsonPath('kendaraan.*.id', [$exclamation->id]);
        $this->list(['q' => '%'])->assertJsonPath('kendaraan.*.id', [$percent->id]);
    }

    public function test_refuses_wrong_filters(): void
    {
        $this->list(['kecamatan' => 'KUPANG TENGAH'])->assertUnprocessable()->assertJsonValidationErrors(['kecamatan' => 'Kecamatan ini tidak ada di Kota Kupang.']);
        $this->list(['kelompok' => 'lunas'])->assertUnprocessable()->assertJsonValidationErrors(['kelompok' => 'Status kepatuhan ini tidak ada.']);
        $this->list(['roda' => 'r6'])->assertUnprocessable()->assertJsonValidationErrors(['roda' => 'Golongan roda ini tidak ada.']);
        $this->list(['bulan' => 0])->assertUnprocessable()->assertJsonValidationErrors(['bulan' => 'Bulan jatuh tempo harus 1 sampai 12.']);
    }

    public function test_lists_at_most_two_hundred_vehicles_but_counts_them_all(): void
    {
        PotentialVehicle::factory()->count(PotentialVehicleController::LISTED + 1)->for($this->current, 'import')->create();

        $this->list()->assertJsonPath('jumlah', PotentialVehicleController::LISTED + 1)->assertJsonCount(PotentialVehicleController::LISTED, 'kendaraan');
    }

    public function test_shows_every_column_of_a_vehicle_of_the_data_shown(): void
    {
        $vehicle = $this->vehicle(['dtd_petugas' => 'Petugas Lapangan', 'njkb' => 15000000]);
        $older = PotentialVehicle::factory()->for(PotentialImport::factory()->unfinished(), 'import')->create();
        $this->actingAs(User::factory()->withModules(DashboardModule::Pemetaan)->create());

        $this->getJson("/api/pemetaan/kendaraan/{$vehicle->id}")
            ->assertOk()
            ->assertJsonPath('ktp', '5371000000000001')
            ->assertJsonPath('mesin', 'MSN001')
            ->assertJsonPath('rangka', 'RKA001')
            ->assertJsonPath('njkb', 15000000)
            ->assertJsonPath('petugasKunjungan', 'Petugas Lapangan')
            ->assertJsonPath('sdStnk', '2029-10-20');
        $this->getJson("/api/pemetaan/kendaraan/{$older->id}")->assertNotFound();
    }

    public function test_answers_an_empty_list_before_the_first_import(): void
    {
        $this->current->delete();

        $this->list()->assertOk()->assertExactJson(['jumlah' => 0, 'kendaraan' => []]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function vehicle(array $attributes = []): PotentialVehicle
    {
        return PotentialVehicle::factory()->for($this->current, 'import')->create($attributes);
    }

    /**
     * @param  array<string, int|string>  $filters
     */
    private function list(array $filters = []): TestResponse
    {
        return $this->actingAs(User::factory()->withModules(DashboardModule::Pemetaan)->create())->getJson('/api/pemetaan/kendaraan?'.http_build_query($filters));
    }
}
