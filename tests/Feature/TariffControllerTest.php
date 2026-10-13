<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class TariffControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[TestWith(['GET'])]
    #[TestWith(['PUT'])]
    public function test_returns_401_to_guests(string $method): void
    {
        $this->json($method, '/api/pengaturan/tarif', $this->tariff())->assertUnauthorized();
    }

    public function test_lets_every_employee_read_the_tariff_for_the_calculator(): void
    {
        $employee = User::factory()->create();

        $response = $this->actingAs($employee)->getJson('/api/pengaturan/tarif');

        $response->assertOk()->assertExactJson([
            'opsenPersen' => 0,
            'dendaPersenPerBulan' => 0,
            'maksBulanDenda' => 0,
            'swdklljMotor' => 0,
            'swdklljMobil' => 0,
            'contohPokok' => 0,
            'contohJenis' => 'motor',
            'contohTelat' => 0,
            'catatan' => '',
        ]);
    }

    public function test_forbids_saving_to_employees_without_the_module_with_403(): void
    {
        $employee = User::factory()->withModules(DashboardModule::Jam)->create();

        $response = $this->actingAs($employee)->putJson('/api/pengaturan/tarif', $this->tariff());

        $response->assertForbidden()->assertExactJson(['message' => 'Modul Tarif & Simulasi belum ditugaskan kepada Anda.']);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_saves_the_tariff_and_logs_it(): void
    {
        $employee = $this->tarifEmployee();

        $response = $this->actingAs($employee)->putJson('/api/pengaturan/tarif', ['dendaPersenPerBulan' => 1.5, 'catatan' => null] + $this->tariff());

        $response->assertOk()->assertExactJson(['dendaPersenPerBulan' => 1.5, 'catatan' => ''] + $this->tariff());
        $this->assertSame($response->json(), Setting::tariff());
        $this->assertDatabaseHas('activities', [
            'user_id' => $employee->id,
            'module' => 'tarif',
            'action' => 'memperbarui',
            'target' => 'Tarif & Simulasi',
        ]);
    }

    public function test_logs_nothing_when_the_same_tariff_is_saved_again(): void
    {
        $employee = $this->tarifEmployee();
        $this->actingAs($employee)->putJson('/api/pengaturan/tarif', ['dendaPersenPerBulan' => 1.5] + $this->tariff())->assertOk();

        // A whole percentage sent as text or with a decimal point is the same rate.
        $response = $this->actingAs($employee)->putJson('/api/pengaturan/tarif', ['opsenPersen' => '66.0', 'dendaPersenPerBulan' => 1.5] + $this->tariff());

        $response->assertOk()->assertJsonPath('opsenPersen', 66);
        $this->assertDatabaseCount('activities', 1);
    }

    public function test_validates_the_rates_example_and_note(): void
    {
        $tariff = [
            'opsenPersen' => 101,
            'dendaPersenPerBulan' => 'satu',
            'maksBulanDenda' => 2.5,
            'swdklljMobil' => 10000001,
            'contohJenis' => 'truk',
            'contohTelat' => -1,
            'catatan' => "Catatan\u{0007}",
        ] + $this->tariff();

        $response = $this->actingAs($this->tarifEmployee())->putJson('/api/pengaturan/tarif', $tariff);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'opsenPersen' => 'Opsen PKB 0 sampai 100%.',
            'dendaPersenPerBulan' => 'Denda keterlambatan harus berupa angka.',
            'maksBulanDenda' => 'Maksimal bulan denda harus bilangan bulat.',
            'swdklljMobil' => 'SWDKLLJ mobil 0 sampai Rp10.000.000.',
            'contohJenis' => 'Jenis kendaraan contoh tidak valid.',
            'contohTelat' => 'Bulan keterlambatan contoh 0 sampai 60.',
            'catatan' => 'Catatan berisi karakter kontrol yang tidak diizinkan.',
        ]);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_requires_every_rate(): void
    {
        $response = $this->actingAs($this->tarifEmployee())->putJson('/api/pengaturan/tarif', ['catatan' => '']);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'opsenPersen', 'dendaPersenPerBulan', 'maksBulanDenda', 'swdklljMotor', 'swdklljMobil', 'contohPokok', 'contohJenis', 'contohTelat',
        ]);
    }

    private function tarifEmployee(): User
    {
        return User::factory()->withModules(DashboardModule::Tarif)->create();
    }

    /**
     * @return array{opsenPersen: int, dendaPersenPerBulan: int, maksBulanDenda: int, swdklljMotor: int, swdklljMobil: int, contohPokok: int, contohJenis: string, contohTelat: int, catatan: string}
     */
    private function tariff(): array
    {
        return [
            'opsenPersen' => 66,
            'dendaPersenPerBulan' => 1,
            'maksBulanDenda' => 24,
            'swdklljMotor' => 35000,
            'swdklljMobil' => 143000,
            'contohPokok' => 285000,
            'contohJenis' => 'motor',
            'contohTelat' => 3,
            'catatan' => 'Tagihan resmi mengikuti notis pajak di loket.',
        ];
    }
}
