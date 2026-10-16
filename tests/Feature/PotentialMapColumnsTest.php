<?php

namespace Tests\Feature;

use App\Actions\BuildPotentialSummary;
use App\Enums\DashboardModule;
use App\Models\Activity;
use App\Models\PotentialImport;
use App\Models\PotentialVehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PotentialMapColumnsTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $administrator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->administrator = User::factory()->administrator()->create();
    }

    public function test_shows_the_columns_the_tables_had_until_they_are_chosen(): void
    {
        $this->actingAs(User::factory()->withModules(DashboardModule::Pemetaan)->create())
            ->getJson('/api/pemetaan/kolom')
            ->assertOk()
            ->assertExactJson([
                'sasaran' => ['pemilik', 'telepon', 'kendaraan', 'kepatuhan', 'tagihan'],
                'wilayah' => ['kendaraan', 'rodaDua', 'rodaTiga', 'rodaEmpat', 'taat', 'belumBayar', 'menunggak', 'persenMenunggak', 'tagihan'],
            ]);
    }

    public function test_only_administrators_choose_the_columns_and_employees_with_the_module_read_them(): void
    {
        $this->getJson('/api/pemetaan/kolom')->assertUnauthorized();
        $this->putJson('/api/pemetaan/kolom', ['wilayah' => ['kendaraan'], 'sasaran' => ['tagihan']])->assertUnauthorized();
        $this->actingAs(User::factory()->withModules(DashboardModule::Penerimaan)->create())
            ->getJson('/api/pemetaan/kolom')
            ->assertForbidden()
            ->assertExactJson(['message' => 'Modul Pemetaan PKB belum ditugaskan kepada Anda.']);
        $this->actingAs(User::factory()->withModules(DashboardModule::Pemetaan)->create())
            ->putJson('/api/pemetaan/kolom', ['wilayah' => ['kendaraan'], 'sasaran' => ['tagihan']])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Hanya Administrator yang dapat mengatur kolom tabel pemetaan PKB.']);
    }

    public function test_keeps_the_chosen_columns_in_the_tables_order_and_logs_a_change_once(): void
    {
        $chosen = ['wilayah' => ['tunggakan', 'kendaraan', 'rodaTiga'], 'sasaran' => ['kunjungan', 'pemilik']];

        $this->actingAs($this->administrator)->putJson('/api/pemetaan/kolom', $chosen)
            ->assertOk()
            ->assertExactJson(['sasaran' => ['pemilik', 'kunjungan'], 'wilayah' => ['kendaraan', 'rodaTiga', 'tunggakan']]);
        $this->actingAs($this->administrator)->putJson('/api/pemetaan/kolom', $chosen)->assertOk();

        $this->actingAs($this->administrator)->getJson('/api/pemetaan/kolom')
            ->assertExactJson(['sasaran' => ['pemilik', 'kunjungan'], 'wilayah' => ['kendaraan', 'rodaTiga', 'tunggakan']]);
        $this->assertSame(
            [['memperbarui', 'Pemetaan PKB', 'pemetaan']],
            Activity::query()->get()->map(fn (Activity $activity): array => [$activity->action, $activity->target, $activity->module->value])->all(),
        );
    }

    public function test_refuses_tables_without_columns_and_unknown_or_repeated_columns(): void
    {
        $this->actingAs($this->administrator)->putJson('/api/pemetaan/kolom', ['wilayah' => [], 'sasaran' => ['tagihan']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['wilayah' => 'Pilih sedikitnya satu kolom untuk tabel kecamatan dan kelurahan.']);
        $this->actingAs($this->administrator)->putJson('/api/pemetaan/kolom', ['wilayah' => ['kendaraan']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sasaran' => 'Pilih sedikitnya satu kolom untuk daftar sasaran penagihan.']);
        $this->actingAs($this->administrator)->putJson('/api/pemetaan/kolom', ['wilayah' => ['ktp'], 'sasaran' => ['tagihan', 'tagihan']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'wilayah.0' => 'Kolom tabel kecamatan dan kelurahan tidak dikenal.',
                'sasaran.0' => 'Kolom daftar sasaran penagihan dipilih lebih dari sekali.',
            ]);
        $this->actingAs($this->administrator)->putJson('/api/pemetaan/kolom', ['wilayah' => ['kendaraan'], 'sasaran' => ['ktp']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sasaran.0' => 'Kolom daftar sasaran penagihan tidak dikenal.']);
        // The collection list's columns never reach the area tables
        $this->actingAs($this->administrator)->putJson('/api/pemetaan/kolom', ['wilayah' => ['telepon'], 'sasaran' => ['tagihan']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['wilayah.0']);
    }

    public function test_tells_the_portal_the_area_table_columns_but_not_the_collection_list_ones(): void
    {
        $import = PotentialImport::factory()->create(['tampil' => true]);
        PotentialVehicle::factory()->count(3)->for($import, 'import')->create();
        app(BuildPotentialSummary::class)->store($import);
        $this->actingAs($this->administrator)->putJson('/api/pemetaan/kolom', ['wilayah' => ['menunggak', 'kendaraan'], 'sasaran' => ['telepon']]);

        $this->getJson('/api/public/pemetaan')
            ->assertOk()
            ->assertJsonPath('kolom', ['kendaraan', 'menunggak'])
            ->assertJsonMissingPath('sasaran');

        $import->update(['tampil' => false]);
        $this->getJson('/api/public/pemetaan')->assertExactJson(['tersedia' => false]);
    }
}
