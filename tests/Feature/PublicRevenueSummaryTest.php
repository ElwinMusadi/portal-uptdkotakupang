<?php

namespace Tests\Feature;

use App\Models\RevenueImport;
use App\Models\RevenueTransaction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PublicRevenueSummaryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_summarises_nothing_until_an_import_is_shown_on_the_portal(): void
    {
        RevenueTransaction::factory()->recycle(RevenueImport::factory()->create())->count(5)->create();

        $this->getJson('/api/public/penerimaan/ringkasan')
            ->assertOk()
            ->assertExactJson(['tersedia' => false, 'tahun' => null, 'dari' => null, 'sampai' => null, 'ringkasan' => null, 'bulan' => []]);
    }

    public function test_summarises_the_latest_published_year_month_by_month_without_the_hidden_months(): void
    {
        $shown = RevenueImport::factory()->create(['tampil' => true]);
        $hidden = RevenueImport::factory()->create();
        RevenueTransaction::factory()->recycle($shown)->count(4)->create(['tanggal' => '2025-12-20']);
        RevenueTransaction::factory()->recycle($shown)->count(2)->create(['tanggal' => '2026-02-10']);
        RevenueTransaction::factory()->recycle($shown)->count(5)->create(['tanggal' => '2026-09-01']);
        RevenueTransaction::factory()->recycle($shown)->count(3)->create(['tanggal' => '2026-10-14']);
        RevenueTransaction::factory()->recycle($hidden)->count(5)->create(['tanggal' => '2026-10-15']);

        $response = $this->getJson('/api/public/penerimaan/ringkasan');

        $none = fn (string $month): array => ['bulan' => $month, 'transaksi' => 0, 'jumlah' => 0];
        $response->assertOk()->assertExactJson([
            'tersedia' => true,
            'tahun' => '2026',
            'dari' => '2026-01-01',
            'sampai' => '2026-10-14',
            'ringkasan' => [
                'transaksi' => 8, 'pkb' => 300000 * 8, 'bbn' => 0, 'dendaPkb' => 0, 'dendaBbn' => 0, 'tunggakan' => 0, 'dendaTunggakan' => 0,
                'opsenPkb' => 198000 * 8, 'opsenBbn' => 0, 'opsenDendaPkb' => 0, 'opsenDendaBbn' => 0, 'opsenTunggakan' => 0, 'opsenDendaTunggakan' => 0,
                'jumlah' => 498000 * 8,
            ],
            'bulan' => [
                $none('2026-01'),
                ['bulan' => '2026-02', 'transaksi' => null, 'jumlah' => null, 'disamarkan' => true],
                $none('2026-03'), $none('2026-04'), $none('2026-05'), $none('2026-06'), $none('2026-07'), $none('2026-08'),
                ['bulan' => '2026-09', 'transaksi' => 5, 'jumlah' => 498000 * 5],
                ['bulan' => '2026-10', 'transaksi' => 3, 'jumlah' => 498000 * 3],
                ['bulan' => '2026-11', 'transaksi' => null, 'jumlah' => null],
                ['bulan' => '2026-12', 'transaksi' => null, 'jumlah' => null],
            ],
        ]);
    }

    public function test_leaves_the_months_before_the_first_published_day_without_figures(): void
    {
        RevenueTransaction::factory()->recycle(RevenueImport::factory()->create(['tampil' => true]))->count(3)->create(['tanggal' => '2026-09-15']);

        $this->getJson('/api/public/penerimaan/ringkasan')
            ->assertOk()
            ->assertJsonPath('dari', '2026-09-15')
            ->assertJsonPath('bulan.7', ['bulan' => '2026-08', 'transaksi' => null, 'jumlah' => null])
            ->assertJsonPath('bulan.8', ['bulan' => '2026-09', 'transaksi' => 3, 'jumlah' => 498000 * 3]);
    }

    public function test_starts_no_session_forbids_caching_and_rejects_query_parameters(): void
    {
        $this->withHeader('Referer', config('app.url').'/')->getJson('/api/public/penerimaan/ringkasan')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertCookieMissing(config('session.cookie'));
        $this->getJson('/api/public/penerimaan/ringkasan?tahun=2025')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['query' => 'Parameter kueri tidak didukung.']);
    }
}
