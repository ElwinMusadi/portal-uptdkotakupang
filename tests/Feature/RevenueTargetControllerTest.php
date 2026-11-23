<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\RevenueTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class RevenueTargetControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_lets_an_employee_of_the_module_save_the_years_target_and_opening_balance(): void
    {
        $employee = User::factory()->withModules(DashboardModule::Penerimaan)->create(['name' => 'Pegawai Penerimaan']);
        $payload = [
            'target' => ['pkb' => 240000209667, 'bbn' => 73264603499, 'dendaPkb' => 8225972907, 'dendaBbn' => 69224567],
            'saldoAwal' => ['sampaiBulan' => 9, 'pkb' => 45307522105, 'tunggakan' => 10838597309, 'bbn' => 36378155302, 'dendaPkb' => 353005312,
                'dendaTunggakan' => 1267078709, 'dendaBbn' => 40718108,
                // Form I.I of October 2026, "s/d bulan lalu" (D-101)
                'kotaKupang' => ['pkb' => 37900679717, 'tunggakan' => 8850743108, 'bbn' => 27448188682, 'dendaPkb' => 280221748,
                    'dendaTunggakan' => 1028873223, 'dendaBbn' => 34696094]],
        ];

        $this->actingAs($employee)->putJson('/api/penerimaan/target/2026', $payload)
            ->assertCreated()
            ->assertJsonPath('tahun', 2026)
            ->assertJsonPath('pkb', 240000209667)
            ->assertJsonPath('dendaBbn', 69224567)
            ->assertJsonPath('saldoAwal', $payload['saldoAwal'])
            ->assertJsonPath('oleh', 'Pegawai Penerimaan');
        $this->assertDatabaseHas('revenue_targets', [
            'tahun' => 2026, 'target_pkb' => 240000209667, 'target_bbn' => 73264603499, 'target_denda_pkb' => 8225972907,
            'target_denda_bbn' => 69224567, 'saldo_sampai_bulan' => 9, 'saldo_pkb' => 45307522105, 'saldo_tunggakan' => 10838597309,
            'saldo_bbn' => 36378155302, 'saldo_denda_pkb' => 353005312, 'saldo_denda_tunggakan' => 1267078709, 'saldo_denda_bbn' => 40718108,
            'saldo_kota_pkb' => 37900679717, 'saldo_kota_tunggakan' => 8850743108, 'saldo_kota_bbn' => 27448188682,
            'saldo_kota_denda_pkb' => 280221748, 'saldo_kota_denda_tunggakan' => 1028873223, 'saldo_kota_denda_bbn' => 34696094,
            'user_id' => $employee->id,
        ]);
        $this->assertDatabaseHas('activities', [
            'user_id' => $employee->id, 'module' => 'penerimaan', 'action' => 'memperbarui', 'target' => 'Target penerimaan 2026',
        ]);

        $this->actingAs($employee)->putJson('/api/penerimaan/target/2026', $payload)->assertOk();
        $this->assertDatabaseCount('activities', 1);
        $this->assertDatabaseCount('revenue_targets', 1);
    }

    public function test_drops_the_opening_balance_when_every_month_comes_from_the_sts_imports(): void
    {
        RevenueTarget::factory()->openingThrough(9, ['pkb' => 50000000])->cityOpening(['pkb' => 40000000])->create();

        $this->actingAs(User::factory()->administrator()->create())
            ->putJson('/api/penerimaan/target/2026', [
                'target' => ['pkb' => 1000, 'bbn' => 2000, 'dendaPkb' => 30, 'dendaBbn' => 40],
                'saldoAwal' => null,
            ])
            ->assertOk()
            ->assertJsonPath('saldoAwal', null);
        $this->assertDatabaseHas('revenue_targets', [
            'tahun' => 2026, 'target_pkb' => 1000, 'saldo_sampai_bulan' => null, 'saldo_pkb' => 0, 'saldo_kota_pkb' => null, 'saldo_kota_denda_bbn' => null,
        ]);
    }

    public function test_keeps_each_year_apart(): void
    {
        RevenueTarget::factory()->create(['tahun' => 2026, 'target_pkb' => 5000]);

        $this->actingAs(User::factory()->administrator()->create())
            ->putJson('/api/penerimaan/target/2027', ['target' => ['pkb' => 1000, 'bbn' => 0, 'dendaPkb' => 0, 'dendaBbn' => 0], 'saldoAwal' => null])
            ->assertCreated()
            ->assertJsonPath('tahun', 2027);
        $this->assertDatabaseHas('revenue_targets', ['tahun' => 2026, 'target_pkb' => 5000]);
        $this->assertDatabaseHas('revenue_targets', ['tahun' => 2027, 'target_pkb' => 1000]);
    }

    public function test_asks_for_every_target_and_whether_there_is_an_opening_balance(): void
    {
        $this->actingAs(User::factory()->administrator()->create())
            ->putJson('/api/penerimaan/target/2026', [])
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors([
                'target' => 'Isi target penerimaan.',
                'target.pkb' => 'Isi target PKB.',
                'target.bbn' => 'Isi target BBNKB.',
                'target.dendaPkb' => 'Isi target denda PKB.',
                'target.dendaBbn' => 'Isi target denda BBNKB.',
                'saldoAwal' => 'Pilih apakah ada saldo awal.',
            ]);
    }

    /**
     * @param  array<string, mixed>  $target
     */
    #[TestWith([['pkb' => -1], 'target.pkb', 'Target PKB tidak boleh negatif.'])]
    #[TestWith([['bbn' => 1.5], 'target.bbn', 'Target BBNKB harus berupa angka rupiah tanpa desimal.'])]
    #[TestWith([['dendaBbn' => 1_000_000_000_000_000], 'target.dendaBbn', 'Target denda BBNKB terlalu besar.'])]
    public function test_takes_whole_rupiah_targets_only(array $target, string $field, string $message): void
    {
        $this->actingAs(User::factory()->administrator()->create())
            ->putJson('/api/penerimaan/target/2026', [
                'target' => [...['pkb' => 0, 'bbn' => 0, 'dendaPkb' => 0, 'dendaBbn' => 0], ...$target],
                'saldoAwal' => null,
            ])
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors([$field => $message]);
    }

    public function test_asks_for_the_month_and_every_amount_of_an_opening_balance(): void
    {
        $this->actingAs(User::factory()->administrator()->create())
            ->putJson('/api/penerimaan/target/2026', [
                'target' => ['pkb' => 0, 'bbn' => 0, 'dendaPkb' => 0, 'dendaBbn' => 0],
                'saldoAwal' => ['sampaiBulan' => 13, 'pkb' => 1000, 'bbn' => 0, 'dendaPkb' => 0, 'dendaTunggakan' => -5, 'dendaBbn' => 0],
            ])
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors([
                'saldoAwal.sampaiBulan' => 'Bulan saldo awal tidak valid.',
                'saldoAwal.tunggakan' => 'Isi saldo awal PKB tunggakan.',
                'saldoAwal.dendaTunggakan' => 'Saldo awal denda PKB tunggakan tidak boleh negatif.',
                'saldoAwal.kotaKupang' => 'Isi saldo awal Kota Kupang.',
            ]);
    }

    /**
     * @param  array<string, mixed>  $city
     */
    #[TestWith([['tunggakan' => null], 'saldoAwal.kotaKupang.tunggakan', 'Isi saldo awal PKB tunggakan Kota Kupang.'])]
    #[TestWith([['dendaPkb' => -1], 'saldoAwal.kotaKupang.dendaPkb', 'Saldo awal denda PKB Kota Kupang tidak boleh negatif.'])]
    #[TestWith([['bbn' => 2.5], 'saldoAwal.kotaKupang.bbn', 'Saldo awal BBNKB Kota Kupang harus berupa angka rupiah tanpa desimal.'])]
    #[TestWith([['dendaBbn' => 1_000_000_000_000_000], 'saldoAwal.kotaKupang.dendaBbn', 'Saldo awal denda BBNKB Kota Kupang terlalu besar.'])]
    #[TestWith([['opsen' => 5], 'saldoAwal.kotaKupang', 'Saldo awal Kota Kupang tidak valid.'])]
    public function test_asks_for_every_amount_of_the_citys_opening_balance(array $city, string $field, string $message): void
    {
        $amounts = ['pkb' => 0, 'tunggakan' => 0, 'bbn' => 0, 'dendaPkb' => 0, 'dendaTunggakan' => 0, 'dendaBbn' => 0];

        $this->actingAs(User::factory()->administrator()->create())
            ->putJson('/api/penerimaan/target/2026', [
                'target' => ['pkb' => 0, 'bbn' => 0, 'dendaPkb' => 0, 'dendaBbn' => 0],
                'saldoAwal' => ['sampaiBulan' => 9, ...$amounts, 'kotaKupang' => array_filter([...$amounts, ...$city], fn (mixed $amount): bool => $amount !== null)],
            ])
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors([$field => $message]);
        $this->assertDatabaseCount('revenue_targets', 0);
    }

    public function test_answers_404_for_a_year_outside_this_century(): void
    {
        $this->actingAs(User::factory()->administrator()->create())
            ->putJson('/api/penerimaan/target/1999', ['target' => ['pkb' => 0, 'bbn' => 0, 'dendaPkb' => 0, 'dendaBbn' => 0], 'saldoAwal' => null])
            ->assertNotFound();
        $this->assertDatabaseCount('revenue_targets', 0);
    }
}
