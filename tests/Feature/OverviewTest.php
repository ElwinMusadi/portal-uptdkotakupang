<?php

namespace Tests\Feature;

use App\Actions\BuildOverview;
use App\Actions\BuildPotentialSummary;
use App\Models\MobileServiceStop;
use App\Models\OverviewLink;
use App\Models\PotentialImport;
use App\Models\PotentialVehicle;
use App\Models\RevenueImport;
use App\Models\RevenueTarget;
use App\Models\RevenueTransaction;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

/**
 * GET /api/ikhtisar (D-099): the Ikhtisar page's figures for the holder of a
 * link, of a period counted back from the latest STS date.
 */
class OverviewTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const string TOKEN = 'TokenUjiIkhtisar0123456789abcdefghijklmnopqrstuv';

    private OverviewLink $link;

    protected function setUp(): void
    {
        parent::setUp();

        $this->link = OverviewLink::factory()->withToken(self::TOKEN)->create();
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function overview(array $query = []): TestResponse
    {
        return $this->withToken(self::TOKEN)->getJson('/api/ikhtisar'.($query === [] ? '' : '?'.http_build_query($query)));
    }

    /**
     * An STS transaction of the counter on the date, imported by the counter's
     * file covering that date.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function transaction(string $date, string $counter = 'kantor', array $attributes = [], int $pkb = 300000): RevenueTransaction
    {
        $import = RevenueImport::factory()
            ->counter($counter, $counter === 'kantor' ? 'Loket SAMSAT' : 'Samsat Keliling 01')
            ->create(['tanggal_awal' => $date, 'tanggal_akhir' => $date]);

        return RevenueTransaction::factory()->for($import, 'revenueImport')->paying(['pkb' => $pkb])->create(['loket' => $counter, 'tanggal' => $date, ...$attributes]);
    }

    public function test_refuses_without_the_token_of_a_link(): void
    {
        $this->getJson('/api/ikhtisar')
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Tautan ini tidak berlaku atau sudah dicabut.']);
        $this->withToken(str_repeat('x', 48))->getJson('/api/ikhtisar')->assertUnauthorized();
        // The token is never read from the address, and nothing is validated before the token
        $this->getJson('/api/ikhtisar?k='.self::TOKEN)->assertUnauthorized();
        $this->getJson('/api/ikhtisar?periode=minggu&loket[]=kantor')->assertUnauthorized();

        $this->link->delete();
        $this->overview()->assertUnauthorized();
    }

    public function test_starts_no_session_and_forbids_caching(): void
    {
        $this->withHeader('Referer', config('app.url').'/ikhtisar')
            ->withCookie(config('session.cookie'), 'sesi-lama')
            ->overview()
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertCookieMissing(config('session.cookie'))
            ->assertCookieMissing('XSRF-TOKEN');
    }

    public function test_keeps_when_the_link_was_last_opened_to_the_minute(): void
    {
        $this->travelTo('2026-11-20 01:00:00');
        $this->overview()->assertOk();
        $this->assertSame('2026-11-20 01:00:00', $this->link->fresh()->terakhir_dibuka->toDateTimeString());

        $this->travelTo('2026-11-20 01:00:59');
        $this->overview()->assertOk();
        $this->assertSame('2026-11-20 01:00:00', $this->link->fresh()->terakhir_dibuka->toDateTimeString());

        $this->travelTo('2026-11-20 01:01:01');
        $this->overview()->assertOk();
        $this->assertSame('2026-11-20 01:01:01', $this->link->fresh()->terakhir_dibuka->toDateTimeString());
    }

    /**
     * @param  array<string, mixed>  $query
     */
    #[TestWith([['loket' => ['kantor']], 'query', 'Parameter kueri tidak didukung.'])]
    #[TestWith([['k' => 'abc'], 'query', 'Parameter kueri tidak didukung.'])]
    #[TestWith([['periode' => 'minggu-ini'], 'periode', 'Pilihan periode tidak dikenal.'])]
    #[TestWith([['periode' => 'bulan-ini', 'dari' => '2026-11-01', 'sampai' => '2026-11-02'], 'periode', 'Pilih periode atau rentang tanggal, tidak keduanya.'])]
    #[TestWith([['dari' => '2026-11-01'], 'sampai', 'Pilih tanggal akhir laporan.'])]
    #[TestWith([['sampai' => '2026-11-01'], 'dari', 'Pilih tanggal awal laporan.'])]
    #[TestWith([['dari' => '2026-11-31', 'sampai' => '2026-12-01'], 'dari', 'Tanggal awal laporan tidak valid.'])]
    #[TestWith([['dari' => '2026-11-05', 'sampai' => '2026-11-01'], 'sampai', 'Tanggal akhir tidak boleh sebelum tanggal awal.'])]
    #[TestWith([['dari' => '2025-01-01', 'sampai' => '2026-01-02'], 'sampai', 'Rentang laporan paling lama satu tahun.'])]
    public function test_validates_the_period(array $query, string $field, string $message): void
    {
        $this->overview($query)->assertUnprocessable()->assertJsonValidationErrors([$field => $message]);
    }

    public function test_counts_the_periods_back_from_the_latest_sts_date(): void
    {
        $this->travelTo('2026-12-15 01:00:00');
        $this->transaction('2026-11-20');
        $this->transaction('2026-11-03');
        $this->transaction('2026-10-14');
        $this->transaction('2026-01-02');

        $this->overview()->assertOk()
            ->assertJsonPath('periode', 'bulan-ini')
            ->assertJsonPath('dari', '2026-11-01')
            ->assertJsonPath('sampai', '2026-11-20')
            ->assertJsonPath('penerimaan.ringkasan.transaksi', 2)
            ->assertJsonPath('penerimaan.pembanding.dari', '2026-10-12')
            ->assertJsonPath('penerimaan.pembanding.sampai', '2026-10-31');
        $this->overview(['periode' => 'hari-terakhir'])->assertOk()
            ->assertJsonPath('dari', '2026-11-20')
            ->assertJsonPath('sampai', '2026-11-20')
            ->assertJsonPath('penerimaan.ringkasan.transaksi', 1)
            ->assertJsonPath('penerimaan.pembanding.dari', '2026-11-03');
        $this->overview(['periode' => 'bulan-lalu'])->assertOk()
            ->assertJsonPath('dari', '2026-10-01')
            ->assertJsonPath('sampai', '2026-10-31')
            ->assertJsonPath('penerimaan.ringkasan.transaksi', 1);
        $this->overview(['periode' => 'tahun-ini'])->assertOk()
            ->assertJsonPath('dari', '2026-01-01')
            ->assertJsonPath('sampai', '2026-11-20')
            ->assertJsonPath('penerimaan.ringkasan.transaksi', 4);
        $this->overview(['dari' => '2026-11-03', 'sampai' => '2026-11-19'])->assertOk()
            ->assertJsonPath('periode', 'rentang')
            ->assertJsonPath('dari', '2026-11-03')
            ->assertJsonPath('sampai', '2026-11-19')
            ->assertJsonPath('penerimaan.ringkasan.transaksi', 1);
        // A whole year of days is still one report
        $this->overview(['dari' => '2025-11-21', 'sampai' => '2026-11-20'])->assertOk();
    }

    public function test_takes_the_month_before_across_the_new_year(): void
    {
        $this->transaction('2027-01-10');
        $this->transaction('2026-12-31');

        $this->overview(['periode' => 'bulan-lalu'])->assertOk()
            ->assertJsonPath('dari', '2026-12-01')
            ->assertJsonPath('sampai', '2026-12-31')
            ->assertJsonPath('penerimaan.ringkasan.transaksi', 1);
    }

    public function test_reports_every_counters_revenue_like_the_dashboard(): void
    {
        $this->transaction('2026-11-20', attributes: ['jenis_daftar' => 'BARU', 'merk' => 'YAMAHA'], pkb: 500000);
        $this->transaction('2026-11-20', 'keliling-01');
        RevenueImport::query()->update(['tampil' => false]);

        $response = $this->overview(['periode' => 'hari-terakhir'])->assertOk()
            ->assertJsonPath('penerimaan.ringkasan.transaksi', 2)
            ->assertJsonPath('penerimaan.ringkasan.pkb', 800000)
            ->assertJsonPath('penerimaan.rincian.loket.*.nama', ['Loket SAMSAT', 'Samsat Keliling 01'])
            ->assertJsonPath('penerimaan.rincian.jenisDaftar.0.nilai', 'BARU')
            ->assertJsonPath('penerimaan.rincian.tanggal.0.nilai', '2026-11-20')
            ->assertJsonPath('penerimaan.loketBuka.0.kunci', 'kantor');

        $this->assertSame([...BuildOverview::BREAKDOWNS, 'tanggal'], array_keys($response->json('penerimaan.rincian')));
        $this->assertSame(['ringkasan', 'pembanding', 'rincian', 'loketBuka'], array_keys($response->json('penerimaan')));
        $this->overview()->assertOk()->assertJsonPath('penerimaan.loketBuka', []);
    }

    public function test_reports_form_i_through_the_end_of_the_period(): void
    {
        $this->transaction('2026-11-20', pkb: 400000);
        $this->transaction('2026-10-14', pkb: 100000);
        RevenueTarget::factory()->for(User::factory()->create(['name' => 'Penyunting Target']))->create(['tahun' => 2026, 'target_pkb' => 10000000]);

        $response = $this->overview()->assertOk()
            ->assertJsonPath('realisasi.tahun', 2026)
            ->assertJsonPath('realisasi.bulan', 11)
            ->assertJsonPath('realisasi.sampai', '2026-11-20')
            ->assertJsonPath('realisasi.baris.0.uraian', 'PKB')
            ->assertJsonPath('realisasi.baris.0.ini', 400000)
            ->assertJsonPath('realisasi.baris.0.lalu', 100000)
            ->assertJsonPath('realisasi.target.pkb', 10000000);
        $this->assertSame(['tahun', 'pkb', 'bbn', 'dendaPkb', 'dendaBbn', 'saldoAwal'], array_keys($response->json('realisasi.target')));

        // A whole month is Form I of that month, without a day it stops at
        $this->overview(['periode' => 'bulan-lalu'])->assertOk()
            ->assertJsonPath('realisasi.bulan', 10)
            ->assertJsonPath('realisasi.sampai', null)
            ->assertJsonPath('realisasi.baris.0.ini', 100000);
    }

    public function test_shows_how_fresh_the_figures_are(): void
    {
        $this->transaction('2026-11-20');
        $this->transaction('2026-11-17');
        $this->transaction('2026-11-18', 'keliling-01');
        MobileServiceStop::factory()->on('2026-11-20', '01')->create();
        MobileServiceStop::factory()->on('2026-11-20', '02')->create();
        PotentialImport::factory()->create(['tanggal_penetapan' => '2026-10-16', 'selesai_pada' => '2026-10-18 02:00:00']);

        $this->overview()->assertOk()
            ->assertJsonPath('pembaruan.stsTerakhir', '2026-11-20')
            ->assertJsonPath('pembaruan.tersedia', ['awal' => '2026-11-17', 'akhir' => '2026-11-20'])
            ->assertJsonPath('pembaruan.loket', [
                ['nilai' => 'kantor', 'nama' => 'Loket SAMSAT', 'sampaiTanggal' => '2026-11-20'],
                ['nilai' => 'keliling-01', 'nama' => 'Samsat Keliling 01', 'sampaiTanggal' => '2026-11-18'],
            ])
            ->assertJsonPath('pembaruan.belumDiimpor.*.kunci', ['keliling-01', 'keliling-02'])
            ->assertJsonPath('pembaruan.dataPotensi', ['tanggal' => '2026-10-16', 'selesai' => '2026-10-18T02:00:00+00:00']);
    }

    public function test_summarises_the_potential_data_without_visit_notes(): void
    {
        $import = PotentialImport::factory()->create(['jumlah' => 2]);
        PotentialVehicle::factory()->for($import, 'import')->create();
        PotentialVehicle::factory()->for($import, 'import')->inArrears(2)->create(['dtd_status' => 'SIAP BAYAR : hubungi kembali minggu depan']);
        app(BuildPotentialSummary::class)->store($import);

        $response = $this->overview()->assertOk()
            ->assertJsonPath('pemetaan.tanggal', '2026-10-16')
            ->assertJsonPath('pemetaan.jumlah.kendaraan', 2)
            ->assertJsonPath('pemetaan.jumlah.kelompok.menunggak', 1)
            ->assertJsonPath('pemetaan.kecamatan.0.kunci', 'KELAPA LIMA')
            ->assertJsonPath('pemetaan.kolom', Setting::potentialMapColumns()['wilayah']);

        $this->assertSame(['tanggal', 'selesai', 'jumlah', 'kecamatan', 'tunggakan', 'jenis', 'bulan', 'kolom'], array_keys($response->json('pemetaan')));
    }

    public function test_answers_before_any_data_is_imported(): void
    {
        $this->travelTo('2026-11-20 20:00:00');

        $this->overview()->assertOk()
            ->assertJsonPath('dari', '2026-11-01')
            ->assertJsonPath('sampai', '2026-11-21')
            ->assertJsonPath('pembaruan', ['stsTerakhir' => null, 'tersedia' => null, 'loket' => [], 'belumDiimpor' => [], 'dataPotensi' => null])
            ->assertJsonPath('penerimaan.ringkasan.transaksi', 0)
            ->assertJsonPath('realisasi.target', null)
            ->assertJsonPath('pemetaan', null);
    }

    public function test_never_shows_a_taxpayers_or_employees_identity(): void
    {
        $this->travelTo('2026-11-20 01:00:00');
        $employee = User::factory()->create(['name' => 'Pegawai Pengimpor Unik']);
        $import = RevenueImport::factory()->for($employee)->create(['tanggal_awal' => '2026-11-20', 'tanggal_akhir' => '2026-11-20', 'nama_berkas' => 'STS_RAHASIA.xls']);
        RevenueTransaction::factory()->for($import, 'revenueImport')->withDetails(['nopol' => 'DH7777QQ', 'nama' => 'WAJIB PAJAK RAHASIA', 'rt' => '019'])->create(['tanggal' => '2026-11-20']);
        RevenueTarget::factory()->for(User::factory()->create(['name' => 'Penyunting Target Unik']))->create(['tahun' => 2026]);
        $potential = PotentialImport::factory()->create(['jumlah' => 1, 'berkas' => 'POTENSI_RAHASIA.xlsx', 'diimpor_oleh' => $employee->id]);
        PotentialVehicle::factory()->for($potential, 'import')->create([
            'nopol' => 'DH8888WW', 'nama' => 'PEMILIK POTENSI RAHASIA', 'alamat' => 'JL. ALAMAT RAHASIA', 'telepon' => '081299990000',
            'ktp' => '5371999999999999', 'dtd_status' => 'TIDAK DITEMUKAN : rumah Bapak Tetangga Rahasia', 'dtd_petugas' => 'PETUGAS RAHASIA',
        ]);
        app(BuildPotentialSummary::class)->store($potential);

        $body = $this->overview(['periode' => 'hari-terakhir'])->assertOk()->getContent();

        foreach (['DH7777QQ', 'WAJIB PAJAK RAHASIA', 'Pegawai Pengimpor Unik', 'STS_RAHASIA', 'Penyunting Target Unik', 'POTENSI_RAHASIA',
            'DH8888WW', 'PEMILIK POTENSI RAHASIA', 'ALAMAT RAHASIA', '081299990000', '5371999999999999', 'Tetangga Rahasia', 'TIDAK DITEMUKAN',
            'PETUGAS RAHASIA', $this->link->nama] as $secret) {
            $this->assertStringNotContainsString($secret, $body);
        }
    }

    public function test_limits_the_requests_from_one_address(): void
    {
        config(['pintu.ikhtisar.requests_per_minute' => 3]);

        $this->overview()->assertOk();
        $this->overview()->assertOk();
        $this->overview()->assertOk();
        $this->overview()
            ->assertTooManyRequests()
            ->assertExactJson(['message' => 'Terlalu banyak permintaan. Silakan coba lagi sebentar lagi.']);
    }
}
