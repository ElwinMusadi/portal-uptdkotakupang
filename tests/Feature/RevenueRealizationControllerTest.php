<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\RevenueImport;
use App\Models\RevenueTarget;
use App\Models\RevenueTransaction;
use App\Models\User;
use Database\Factories\RevenueTargetFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;
use ZipArchive;

class RevenueRealizationControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[TestWith(['GET', '/api/penerimaan/realisasi?bulan=2026-10'])]
    #[TestWith(['GET', '/api/penerimaan/realisasi/unduh?bulan=2026-10'])]
    #[TestWith(['PUT', '/api/penerimaan/target/2026'])]
    public function test_returns_401_to_guests_and_403_to_employees_without_the_module(string $method, string $uri): void
    {
        $this->json($method, $uri)->assertUnauthorized();

        $this->actingAs(User::factory()->withModules(DashboardModule::Statistik)->create())
            ->json($method, $uri)
            ->assertForbidden()
            ->assertExactJson(['message' => 'Modul Laporan Penerimaan belum ditugaskan kepada Anda.']);
    }

    public function test_sums_every_counter_per_kind_of_revenue_against_the_target(): void
    {
        $this->smallTarget()->create();
        $this->october();

        $response = $this->actingAs($this->revenueEmployee())->getJson('/api/penerimaan/realisasi?bulan=2026-10');

        $response->assertOk()
            ->assertJsonPath('tahun', 2026)
            ->assertJsonPath('bulan', 10)
            ->assertJsonPath('sampai', null)
            ->assertJsonPath('tersedia', true)
            ->assertJsonPath('sampaiTanggal', '2026-10-20')
            ->assertJsonPath('target.pkb', 10000000)
            ->assertJsonPath('target.saldoAwal', null)
            ->assertJsonPath('loket', [
                ['nilai' => 'keliling-01', 'nama' => 'Samsat Keliling 01', 'transaksi' => 1, 'jumlah' => 9090000, 'sampaiTanggal' => '2026-10-20'],
                ['nilai' => 'kantor', 'nama' => 'Loket SAMSAT', 'transaksi' => 1, 'jumlah' => 306000, 'sampaiTanggal' => '2026-10-01'],
            ]);
        $this->assertSame([
            $this->row('rincian', '1', 'PKB', 2, 10000000, 1000000, 300000, 1300000, null),
            $this->row('rincian', null, 'PKB Tunggakan', 0, null, 200000, 0, 200000, null),
            $this->row('subjumlah', null, 'JUMLAH PKB', 1, 10000000, 1200000, 300000, 1500000, 15),
            $this->row('rincian', '2', 'BBNKB', 1, 20000000, 0, 9000000, 9000000, 45),
            $this->row('jumlah', null, 'JUMLAH PAJAK', 1, 30000000, 1200000, 9300000, 10500000, 35),
            $this->row('rincian', '3', 'Pendapatan Denda PKB', 2, 100000, 0, 5000, 5000, null),
            $this->row('rincian', null, 'Pendapatan Denda PKB Tunggakan', 0, null, 0, 1000, 1000, null),
            $this->row('subjumlah', null, 'JUMLAH DENDA PKB', 1, 100000, 0, 6000, 6000, 6),
            $this->row('rincian', '4', 'Pendapatan Denda BBNKB', 1, 400000, 0, 90000, 90000, 22.5),
            $this->row('jumlah', null, 'Jumlah Pendapatan Denda Pajak', 1, 500000, 0, 96000, 96000, 19.2),
            $this->row('total', null, 'TOTAL', 1, 30500000, 1200000, 9396000, 10596000, 34.741),
        ], $response->json('baris'));
    }

    public function test_stops_the_month_at_the_given_day(): void
    {
        $this->smallTarget()->create();
        $this->october();

        $response = $this->actingAs($this->revenueEmployee())->getJson('/api/penerimaan/realisasi?bulan=2026-10&sampai=2026-10-01');

        $response->assertOk()
            ->assertJsonPath('sampai', '2026-10-01')
            ->assertJsonPath('sampaiTanggal', '2026-10-01')
            ->assertJsonPath('loket', [
                ['nilai' => 'kantor', 'nama' => 'Loket SAMSAT', 'transaksi' => 1, 'jumlah' => 306000, 'sampaiTanggal' => '2026-10-01'],
            ]);
        $this->assertSame(['lalu' => 1000000, 'ini' => 300000], $this->amounts($response, 0));
        $this->assertSame(['lalu' => 0, 'ini' => 0], $this->amounts($response, 3));
        $this->assertSame(
            $this->row('total', null, 'TOTAL', 1, 30500000, 1200000, 306000, 1506000, 4.9377),
            $response->json('baris.10'),
        );
    }

    #[TestWith(['2026-11-01', 'Tanggal batas harus di dalam bulan laporan.'])]
    #[TestWith(['2026-09-30', 'Tanggal batas harus di dalam bulan laporan.'])]
    #[TestWith(['2026-10-32', 'Tanggal batas laporan tidak valid.'])]
    #[TestWith(['7-10-2026', 'Tanggal batas laporan tidak valid.'])]
    public function test_asks_for_a_day_of_the_month(string $day, string $message): void
    {
        $this->actingAs($this->revenueEmployee())
            ->getJson("/api/penerimaan/realisasi?bulan=2026-10&sampai={$day}")
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['sampai' => $message]);
    }

    public function test_counts_the_opening_balance_instead_of_the_transactions_of_the_months_it_covers(): void
    {
        $this->smallTarget()->openingThrough(9, ['pkb' => 50000000, 'denda_bbn' => 70000])->create();
        $import = RevenueImport::factory()->create();
        RevenueTransaction::factory()->for($import)->paying(['pkb' => 1000000])->create(['tanggal' => '2026-09-10']);
        RevenueTransaction::factory()->for($import)->paying(['pkb' => 300000])->create(['tanggal' => '2026-10-14']);
        RevenueTransaction::factory()->for($import)->paying(['pkb' => 400000])->create(['tanggal' => '2026-11-05']);
        $employee = $this->revenueEmployee();

        $october = $this->actingAs($employee)->getJson('/api/penerimaan/realisasi?bulan=2026-10')->assertOk();
        $november = $this->actingAs($employee)->getJson('/api/penerimaan/realisasi?bulan=2026-11')->assertOk();

        $this->assertSame(['lalu' => 50000000, 'ini' => 300000], $this->amounts($october, 0));
        $this->assertSame(['lalu' => 50300000, 'ini' => 400000], $this->amounts($november, 0));
        $this->assertSame(['lalu' => 70000, 'ini' => 0], $this->amounts($november, 8));
        $november->assertJsonPath('target.saldoAwal', [
            'sampaiBulan' => 9, 'pkb' => 50000000, 'tunggakan' => 0, 'bbn' => 0, 'dendaPkb' => 0, 'dendaTunggakan' => 0, 'dendaBbn' => 70000,
        ]);
    }

    public function test_has_no_report_for_a_month_the_opening_balance_covers(): void
    {
        $this->smallTarget()->openingThrough(9, ['pkb' => 50000000])->create();
        RevenueTransaction::factory()->create(['tanggal' => '2026-09-10']);

        $this->actingAs($this->revenueEmployee())->getJson('/api/penerimaan/realisasi?bulan=2026-09')
            ->assertOk()
            ->assertJsonPath('tersedia', false)
            ->assertJsonPath('sampaiTanggal', null)
            ->assertJsonPath('loket', [])
            ->assertJsonPath('baris', [])
            ->assertJsonPath('target.saldoAwal.sampaiBulan', 9);
    }

    public function test_reports_without_shares_until_the_year_has_a_target(): void
    {
        RevenueTarget::factory()->create(['tahun' => 2025]);
        $this->october();

        $response = $this->actingAs($this->revenueEmployee())->getJson('/api/penerimaan/realisasi?bulan=2026-10');

        $response->assertOk()->assertJsonPath('target', null);
        $this->assertSame(
            $this->row('total', null, 'TOTAL', 1, 0, 1200000, 9396000, 10596000, null),
            $response->json('baris.10'),
        );
    }

    #[TestWith(['', 'Pilih bulan laporan.'])]
    #[TestWith(['2026-13', 'Bulan laporan tidak valid.'])]
    #[TestWith(['2026-1', 'Bulan laporan tidak valid.'])]
    #[TestWith(['1999-12', 'Bulan laporan tidak valid.'])]
    public function test_asks_for_a_valid_month(string $month, string $message): void
    {
        $this->actingAs($this->revenueEmployee())
            ->getJson("/api/penerimaan/realisasi?bulan={$month}")
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['bulan' => $message]);
    }

    public function test_downloads_the_month_as_a_form_i_workbook(): void
    {
        $this->smallTarget()->create();
        $this->october();

        $response = $this->actingAs($this->revenueEmployee())->get('/api/penerimaan/realisasi/unduh?bulan=2026-10');

        $response->assertOk()
            ->assertDownload('realisasi-penerimaan-2026-10.xlsx')
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $this->assertNotFalse($zip->getFromName('xl/styles.xml'));
        $zip->close();
        unlink($path);

        $this->assertStringContainsString('<t xml:space="preserve">BULAN OKTOBER</t>', $sheet);
        $this->assertStringContainsString('<t xml:space="preserve">TAHUN 2026</t>', $sheet);
        $this->assertStringContainsString('<c r="B11" s="6" t="inlineStr"><is><t xml:space="preserve">PKB</t></is></c><c r="C11" s="8"><v>10000000</v></c>'
            .'<c r="D11" s="8"><v>1000000</v></c><c r="E11" s="8"><v>300000</v></c><c r="F11" s="8"><v>1300000</v></c><c r="G11" s="9"/>', $sheet);
        $this->assertStringContainsString('<c r="B12" s="6" t="inlineStr"><is><t xml:space="preserve">PKB Tunggakan</t></is></c><c r="C12" s="8"/>', $sheet);
        $this->assertStringContainsString('<c r="F21" s="11"><v>10596000</v></c><c r="G21" s="12"><v>0.34741</v></c>', $sheet);
        $this->assertStringContainsString('<mergeCell ref="A11:A12"/><mergeCell ref="C11:C12"/>', $sheet);
        $this->assertStringContainsString('<mergeCell ref="A21:B21"/>', $sheet);
        $this->assertStringNotContainsString('KEADAAN', $sheet);
    }

    public function test_writes_the_day_the_month_stops_at_under_the_year(): void
    {
        $this->smallTarget()->create();
        $this->october();

        $response = $this->actingAs($this->revenueEmployee())->get('/api/penerimaan/realisasi/unduh?bulan=2026-10&sampai=2026-10-07');

        $response->assertOk()->assertDownload('realisasi-penerimaan-2026-10-sd-07.xlsx');
        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        unlink($path);

        $this->assertStringContainsString('<row r="6"><c r="A6" s="1" t="inlineStr"><is><t xml:space="preserve">KEADAAN S/D 7 OKTOBER 2026</t></is></c></row>', $sheet);
        $this->assertStringContainsString('<row r="8"><c r="A8" s="3" t="inlineStr"><is><t xml:space="preserve">GABUNGAN</t>', $sheet);
        $this->assertStringContainsString('<row r="10" ht="30" customHeight="1">', $sheet);
        $this->assertStringContainsString('<c r="E12" s="8"><v>300000</v></c>', $sheet);
        $this->assertStringContainsString('<mergeCell ref="A6:G6"/>', $sheet);
        $this->assertStringContainsString('<mergeCell ref="A12:A13"/><mergeCell ref="C12:C13"/>', $sheet);
        $this->assertStringContainsString('<mergeCell ref="A22:B22"/>', $sheet);
    }

    public function test_refuses_to_download_a_month_the_opening_balance_covers(): void
    {
        $this->smallTarget()->openingThrough(9, ['pkb' => 50000000])->create();

        $this->actingAs($this->revenueEmployee())
            ->getJson('/api/penerimaan/realisasi/unduh?bulan=2026-09')
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['bulan' => 'Realisasi bulan ini tercatat dalam saldo awal, jadi tidak ada laporan per bulannya.']);
    }

    /**
     * Targets small enough to read the shares at a glance.
     */
    private function smallTarget(): RevenueTargetFactory
    {
        return RevenueTarget::factory()->state([
            'target_pkb' => 10000000, 'target_bbn' => 20000000, 'target_denda_pkb' => 100000, 'target_denda_bbn' => 400000,
        ]);
    }

    /**
     * The office on the first and last day of the year before October and on
     * 1 October, Samsat Keliling 01 later in October, and a transaction on
     * each side of the year to October that does not count. Opsen does not
     * count either.
     */
    private function october(): void
    {
        $office = RevenueImport::factory()->create();
        $unit = RevenueImport::factory()->counter('keliling-01', 'Samsat Keliling 01')->create();
        RevenueTransaction::factory()->for($office)->paying(['pkb' => 999])->create(['tanggal' => '2025-12-31']);
        RevenueTransaction::factory()->for($office)->paying(['tunggakan' => 200000])->create(['tanggal' => '2026-01-01']);
        RevenueTransaction::factory()->for($office)->paying(['pkb' => 1000000, 'opsen_pkb' => 660000])->create(['tanggal' => '2026-09-30']);
        RevenueTransaction::factory()->for($office)->paying(['pkb' => 300000, 'denda_pkb' => 5000, 'denda_tunggakan' => 1000])->create(['tanggal' => '2026-10-01']);
        RevenueTransaction::factory()->for($unit)->paying(['bbn' => 9000000, 'denda_bbn' => 90000])->create(['loket' => 'keliling-01', 'tanggal' => '2026-10-20']);
        RevenueTransaction::factory()->for($office)->paying(['pkb' => 777])->create(['tanggal' => '2026-11-01']);
    }

    /**
     * A row as the JSON response holds it; a whole share has no decimals.
     *
     * @return array{jenis: string, no: ?string, uraian: string, baris: int, target: ?int, lalu: int, ini: int, sampaiIni: int, persen: int|float|null}
     */
    private function row(string $kind, ?string $number, string $label, int $span, ?int $target, int $before, int $current, int $through, int|float|null $share): array
    {
        return [
            'jenis' => $kind, 'no' => $number, 'uraian' => $label, 'baris' => $span, 'target' => $target,
            'lalu' => $before, 'ini' => $current, 'sampaiIni' => $through, 'persen' => $share,
        ];
    }

    /**
     * @return array{lalu: int, ini: int}
     */
    private function amounts(TestResponse $response, int $row): array
    {
        return ['lalu' => $response->json("baris.{$row}.lalu"), 'ini' => $response->json("baris.{$row}.ini")];
    }

    private function revenueEmployee(): User
    {
        return User::factory()->withModules(DashboardModule::Penerimaan)->create();
    }
}
