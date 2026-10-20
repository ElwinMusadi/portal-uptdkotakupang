<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\RevenueImport;
use App\Models\RevenueTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class FormNumberReportControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_401_to_guests_and_403_to_employees_without_the_module(): void
    {
        $uri = '/api/penerimaan/nomoratur?dari=2026-10-14&sampai=2026-10-14';
        $this->getJson($uri)->assertUnauthorized();

        $this->actingAs(User::factory()->withModules(DashboardModule::Statistik)->create())
            ->getJson($uri)
            ->assertForbidden()
            ->assertExactJson(['message' => 'Modul Laporan Penerimaan belum ditugaskan kepada Anda.']);
    }

    public function test_lists_a_days_runs_missing_numbers_and_transactions_without_a_number(): void
    {
        $this->october();

        $response = $this->actingAs($this->revenueEmployee())->getJson('/api/penerimaan/nomoratur?dari=2026-10-14&sampai=2026-10-14');

        $response->assertOk()
            ->assertJsonPath('dari', '2026-10-14')
            ->assertJsonPath('sampai', '2026-10-14')
            ->assertJsonPath('batasTerlewat', 50)
            ->assertJsonPath('ringkasan', ['lembar' => 6, 'transaksi' => 6, 'tanpaNomoratur' => 1, 'belumTercatat' => 1, 'terlewat' => 2, 'ganda' => 0])
            ->assertJsonPath('ganda', [])
            ->assertJsonCount(1, 'loket')
            ->assertJsonPath('loket.0.hari', [[
                'tanggal' => '2026-10-14',
                'lembar' => 6,
                'transaksi' => 6,
                'tanpaNomoratur' => 1,
                'belumTercatat' => 1,
                'rentang' => [
                    ['awal' => '0000101', 'akhir' => '0000103', 'lembar' => 3],
                    ['awal' => '0000106', 'akhir' => '0000106', 'lembar' => 1],
                    ['awal' => '0000200', 'akhir' => '0000201', 'lembar' => 2],
                ],
                'terlewat' => [['awal' => '0000104', 'akhir' => '0000105', 'jumlah' => 2]],
                'ganda' => [],
            ]]);
    }

    public function test_sums_each_counter_over_the_month_and_finds_numbers_used_twice_anywhere(): void
    {
        $this->october();

        $response = $this->actingAs($this->revenueEmployee())->getJson('/api/penerimaan/nomoratur?dari=2026-10-01&sampai=2026-10-31');

        $response->assertOk()
            ->assertJsonPath('ringkasan', ['lembar' => 9, 'transaksi' => 11, 'tanpaNomoratur' => 1, 'belumTercatat' => 1, 'terlewat' => 2, 'ganda' => 2])
            ->assertJsonPath('ganda', [[
                'nomor' => '0000106',
                'kali' => 2,
                'pemakaian' => [
                    ['loket' => 'Loket SAMSAT', 'tanggal' => '2026-10-14', 'jenisDaftar' => 'BARU'],
                    ['loket' => 'Samsat Keliling 01', 'tanggal' => '2026-10-15', 'jenisDaftar' => 'Teliti Ulang / Pengesahan'],
                ],
            ], [
                'nomor' => '0000201',
                'kali' => 2,
                'pemakaian' => [
                    ['loket' => 'Loket SAMSAT', 'tanggal' => '2026-10-14', 'jenisDaftar' => 'Teliti Ulang / Pengesahan'],
                    ['loket' => 'Loket SAMSAT', 'tanggal' => '2026-10-15', 'jenisDaftar' => 'Teliti Ulang / Pengesahan'],
                ],
            ]]);
        $this->assertSame(
            [
                ['nilai' => 'kantor', 'nama' => 'Loket SAMSAT', 'lembar' => 8, 'transaksi' => 9, 'tanpaNomoratur' => 1, 'belumTercatat' => 1, 'terlewat' => 2, 'ganda' => 2],
                ['nilai' => 'keliling-01', 'nama' => 'Samsat Keliling 01', 'lembar' => 2, 'transaksi' => 2, 'tanpaNomoratur' => 0, 'belumTercatat' => 0, 'terlewat' => 0, 'ganda' => 1],
            ],
            array_map(fn (array $counter): array => array_diff_key($counter, ['hari' => true]), $response->json('loket')),
        );
        $this->assertSame(['2026-10-13', '2026-10-14', '2026-10-15'], array_column($response->json('loket.0.hari'), 'tanggal'));
        $this->assertSame(['0000106', '0000201'], $response->json('loket.0.hari.1.ganda'));
        $this->assertSame(['0000201'], $response->json('loket.0.hari.2.ganda'));
        $this->assertSame(
            [['awal' => '0000106', 'akhir' => '0000106', 'lembar' => 1], ['awal' => '0000500', 'akhir' => '0000500', 'lembar' => 1]],
            $response->json('loket.1.hari.0.rentang'),
        );
        $this->assertSame([], $response->json('loket.1.hari.0.terlewat'));
    }

    public function test_reports_on_one_counter(): void
    {
        $this->october();

        $response = $this->actingAs($this->revenueEmployee())->getJson('/api/penerimaan/nomoratur?dari=2026-10-01&sampai=2026-10-31&loket=keliling-01');

        $response->assertOk()
            ->assertJsonPath('ringkasan.lembar', 2)
            ->assertJsonPath('ganda', [])
            ->assertJsonCount(1, 'loket')
            ->assertJsonPath('loket.0.nilai', 'keliling-01');
    }

    public function test_counts_missing_numbers_only_between_runs_at_most_fifty_apart(): void
    {
        $import = RevenueImport::factory()->create();
        foreach (['0001000', '0001051', '0002000', '0002052', '0003000', '0003002'] as $number) {
            RevenueTransaction::factory()->for($import)->create(['nomoratur' => $number]);
        }

        $response = $this->actingAs($this->revenueEmployee())->getJson('/api/penerimaan/nomoratur?dari=2026-10-14&sampai=2026-10-14');

        $response->assertJsonPath('loket.0.hari.0.terlewat', [
            ['awal' => '0001001', 'akhir' => '0001050', 'jumlah' => 50],
            ['awal' => '0003001', 'akhir' => '0003001', 'jumlah' => 1],
        ])
            ->assertJsonPath('loket.0.hari.0.lembar', 6)
            ->assertJsonCount(6, 'loket.0.hari.0.rentang')
            ->assertJsonPath('ringkasan.terlewat', 51);
    }

    public function test_lists_the_counters_with_the_most_forms_first(): void
    {
        $office = RevenueImport::factory()->create();
        $unit = RevenueImport::factory()->counter('keliling-01', 'Samsat Keliling 01')->create();
        RevenueTransaction::factory()->for($office)->create(['tanggal' => '2026-10-13', 'nomoratur' => '0000001']);
        RevenueTransaction::factory()->for($unit)->create(['loket' => 'keliling-01', 'nomoratur' => '0000002']);
        RevenueTransaction::factory()->for($unit)->create(['loket' => 'keliling-01', 'nomoratur' => '0000003']);

        $response = $this->actingAs($this->revenueEmployee())->getJson('/api/penerimaan/nomoratur?dari=2026-10-13&sampai=2026-10-14');

        $this->assertSame(['keliling-01', 'kantor'], array_column($response->json('loket'), 'nilai'));
    }

    #[TestWith(['dari=2026-10-01&sampai=2026-11-01', 'sampai', 'Laporan nomoratur paling lama 31 hari.'])]
    #[TestWith(['dari=2026-10-14&sampai=2026-10-13', 'sampai', 'Tanggal akhir tidak boleh sebelum tanggal awal.'])]
    #[TestWith(['dari=14-10-2026&sampai=2026-10-14', 'dari', 'Tanggal awal laporan tidak valid.'])]
    public function test_asks_for_a_period_of_at_most_a_month(string $query, string $field, string $message): void
    {
        $this->actingAs($this->revenueEmployee())
            ->getJson("/api/penerimaan/nomoratur?{$query}")
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors([$field => $message]);
    }

    /**
     * The office on 13, 14 and 15 October, with a run, a short gap, a jump
     * to another stack, a transaction without a form and one imported before
     * form numbers were kept on the 14th, and one of the 14th's numbers again
     * on the 15th; Samsat Keliling 01 on the 15th, using another of the
     * office's numbers again.
     */
    private function october(): void
    {
        $office = RevenueImport::factory()->create();
        $unit = RevenueImport::factory()->counter('keliling-01', 'Samsat Keliling 01')->create();
        RevenueTransaction::factory()->for($office)->create(['tanggal' => '2026-10-13', 'nomoratur' => '0000100']);
        foreach (['0000103', '0000101', '0000102', '0000200', '0000201'] as $number) {
            RevenueTransaction::factory()->for($office)->create(['nomoratur' => $number]);
        }
        RevenueTransaction::factory()->for($office)->create(['nomoratur' => '0000106', 'jenis_daftar' => 'BARU']);
        RevenueTransaction::factory()->for($office)->create(['nomoratur' => '-', 'jenis_daftar' => 'MUTASI KELUAR LUAR PROPINSI']);
        RevenueTransaction::factory()->for($office)->create(['nomoratur' => null]);
        RevenueTransaction::factory()->for($office)->create(['tanggal' => '2026-10-15', 'nomoratur' => '0000107']);
        RevenueTransaction::factory()->for($office)->create(['tanggal' => '2026-10-15', 'nomoratur' => '0000201']);
        RevenueTransaction::factory()->for($unit)->create(['loket' => 'keliling-01', 'tanggal' => '2026-10-15', 'nomoratur' => '0000106']);
        RevenueTransaction::factory()->for($unit)->create(['loket' => 'keliling-01', 'tanggal' => '2026-10-15', 'nomoratur' => '0000500']);
    }

    private function revenueEmployee(): User
    {
        return User::factory()->withModules(DashboardModule::Penerimaan)->create();
    }
}
