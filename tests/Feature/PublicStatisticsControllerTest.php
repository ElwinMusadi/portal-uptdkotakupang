<?php

namespace Tests\Feature;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\Setting;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use PDOException;
use Tests\TestCase;

class PublicStatisticsControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_publishes_nothing_until_the_statistics_are_first_saved(): void
    {
        $response = $this->getJson('/api/public/statistik');

        $response->assertOk()->assertExactJson(['tahun' => '', 'catatan' => '', 'set' => [], 'diperbarui' => null]);
    }

    public function test_publishes_no_figure_while_the_statistics_are_an_illustration(): void
    {
        $this->saveStatistics('ilustrasi');

        $response = $this->getJson('/api/public/statistik');

        $response->assertOk()->assertExactJson(['tahun' => '', 'catatan' => '', 'set' => [], 'diperbarui' => null]);
        $this->assertStringNotContainsString('12480', $response->getContent());
    }

    public function test_publishes_verified_statistics_without_tile_ids_or_status(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 08:00:00', 'UTC'));
        $this->saveStatistics('terverifikasi');

        $response = $this->getJson('/api/public/statistik');

        $response->assertOk()
            ->assertJsonPath('tahun', '2026')
            ->assertJsonPath('catatan', 'Angka triwulan III telah disetujui pimpinan.')
            ->assertJsonPath('diperbarui', '2026-10-13T08:00:00+00:00')
            ->assertJsonPath('set.*.kunci', ['transaksi', 'keliling', 'kepuasan'])
            ->assertJsonPath('set.0', [
                'kunci' => 'transaksi',
                'label' => 'Transaksi pelayanan',
                'sub' => 'Transaksi pelayanan · UPTD Kota Kupang',
                'judulGrafik' => 'Transaksi per bulan',
                'satuan' => 'transaksi',
                'tiles' => [
                    ['label' => 'Total transaksi', 'nilai' => 12480, 'satuan' => '', 'perubahan' => 4.2, 'naikBaik' => true],
                    ['label' => 'Pajak tahunan', 'nilai' => 0, 'satuan' => '', 'perubahan' => 0, 'naikBaik' => true],
                    ['label' => 'Perpanjangan 5 tahun', 'nilai' => 0, 'satuan' => '', 'perubahan' => 0, 'naikBaik' => true],
                    ['label' => 'Mutasi & BBN', 'nilai' => 0, 'satuan' => '', 'perubahan' => 0, 'naikBaik' => true],
                ],
                'seri' => [880, 910, 1020, 960, 1040, 1100, 990, 1080, 1150, 1120, 1060, 1160],
            ]);
        $this->assertArrayNotHasKey('status', $response->json());
    }

    public function test_starts_no_session_forbids_caching_and_rejects_query_parameters(): void
    {
        $this->withHeader('Referer', config('app.url').'/')->getJson('/api/public/statistik')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertCookieMissing(config('session.cookie'));
        $this->get('/api/public/statistik?set=transaksi')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['query' => 'Parameter kueri tidak didukung.']);
    }

    public function test_returns_503_when_the_source_cannot_be_read(): void
    {
        DB::table('settings')->exists();
        DB::beforeExecuting(function (string $query): void {
            if (preg_match('/\bsettings\b/', $query) === 1) {
                throw new QueryException(DB::getName(), $query, [], new PDOException('SQLSTATE[HY000] [2002] Connection refused'));
            }
        });
        Exceptions::fake();

        $this->getJson('/api/public/statistik')->assertServiceUnavailable();

        Exceptions::assertReported(PublicContentUnavailableException::class);
    }

    public function test_leaves_out_the_revenue_category_of_statistics_saved_before_it_left(): void
    {
        $this->saveStatistics('terverifikasi', withRevenue: true);

        $this->getJson('/api/public/statistik')->assertOk()->assertJsonPath('set.*.kunci', ['transaksi', 'keliling', 'kepuasan']);
    }

    private function saveStatistics(string $status, bool $withRevenue = false): void
    {
        $statistics = Setting::statistics();
        $statistics['status'] = $status;
        $statistics['tahun'] = '2026';
        $statistics['catatan'] = 'Angka triwulan III telah disetujui pimpinan.';
        $statistics['set'][0]['tiles'][0]['nilai'] = 12480;
        $statistics['set'][0]['tiles'][0]['perubahan'] = 4.2;
        $statistics['set'][0]['seri'] = [880, 910, 1020, 960, 1040, 1100, 990, 1080, 1150, 1120, 1060, 1160];
        if ($withRevenue) {
            // Saved before Penerimaan pajak left the statistics (D-055)
            array_splice($statistics['set'], 1, 0, [['kunci' => 'penerimaan', 'label' => 'Penerimaan pajak', 'sub' => '', 'judulGrafik' => 'Penerimaan PKB per bulan', 'satuan' => 'juta rupiah', 'tiles' => $statistics['set'][0]['tiles'], 'seri' => array_fill(0, 12, 0)]]);
        }
        (new Setting)->forceFill(['name' => Setting::STATISTICS, 'value' => $statistics])->save();
    }
}
