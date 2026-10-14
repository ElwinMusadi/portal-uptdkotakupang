<?php

namespace Tests\Feature;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\MobileServiceStop;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use PDOException;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PublicMobileServiceStopControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_what_is_served_each_day_until_the_sunday_of_next_week_without_internal_fields(): void
    {
        // Wednesday 14 October 2026 in Kupang
        $this->travelTo(Carbon::parse('2026-10-14 10:00:00', 'Asia/Makassar'));
        MobileServiceStop::factory()->on('2026-10-17', '01')->create(['mulai' => '17:00', 'selesai' => '19:00', 'lokasi' => 'Tamnos']);
        MobileServiceStop::factory()->on('2026-10-17', '02')->create([
            'mulai' => '06:00',
            'selesai' => '09:00',
            'lokasi' => 'CFD Eltari',
            'kecamatan' => 'Kec. Oebobo',
            'layanan' => 'Pajak tahunan',
            'catatan' => 'Bawa STNK.',
        ]);
        MobileServiceStop::factory()->on('2026-10-25', '03')->create(['lokasi' => 'Festival Kota']);
        MobileServiceStop::factory()->on('2026-10-13')->create(['lokasi' => 'Kemarin']);
        MobileServiceStop::factory()->on('2026-10-26')->create(['lokasi' => 'Setelah minggu depan']);
        MobileServiceStop::factory()->on('2026-10-15')->inactive()->create(['lokasi' => 'Tidak aktif']);
        MobileServiceStop::factory()->outlet('Samsat Corner', [1, 2, 3, 4, 5])->create(['mulai' => '08:00', 'selesai' => '15:00', 'lokasi' => 'Lippo Plaza']);
        MobileServiceStop::factory()->outlet('Mall Pelayanan Publik')->create(['tanggal_selesai' => '2026-10-10', 'lokasi' => 'Sudah tutup']);
        MobileServiceStop::factory()->counter('2026-10-19', '2026-10-23', 'Loket Kantor Lurah Oebobo')->create(['mulai' => '08:00', 'selesai' => '12:00', 'lokasi' => 'Kantor Lurah Oebobo']);
        MobileServiceStop::factory()->counter('2026-11-10', '2026-11-15', 'Loket Pameran Pembangunan', [0, 1, 2, 3, 4, 5, 6])->create(['lokasi' => 'Arena Pameran']);
        MobileServiceStop::factory()->counter('2026-10-05', '2026-10-12', 'Loket Pasar')->create(['lokasi' => 'Sudah berakhir']);

        $response = $this->getJson('/api/public/keliling');

        $response->assertOk()
            ->assertJsonPath('dari', '2026-10-14')
            ->assertJsonPath('sampai', '2026-10-25')
            ->assertJsonPath('jadwal.3', [
                'tanggal' => '2026-10-17',
                'jenis' => 'keliling',
                'nama' => 'Samsat Keliling 02',
                'mulai' => '06:00',
                'selesai' => '09:00',
                'lokasi' => 'CFD Eltari',
                'kecamatan' => 'Kec. Oebobo',
                'layanan' => 'Pajak tahunan',
                'catatan' => 'Bawa STNK.',
            ])
            ->assertJsonPath('gerai', [[
                'nama' => 'Samsat Corner',
                'tanggalMulai' => null,
                'tanggalSelesai' => null,
                'hari' => [1, 2, 3, 4, 5],
                'mulai' => '08:00',
                'selesai' => '15:00',
                'lokasi' => 'Lippo Plaza',
                'kecamatan' => 'Kec. Oebobo',
                'layanan' => 'Pajak tahunan',
                'catatan' => '',
            ]]);
        $this->assertSame([
            '2026-10-14 Samsat Corner', '2026-10-15 Samsat Corner', '2026-10-16 Samsat Corner',
            '2026-10-17 Samsat Keliling 02', '2026-10-17 Samsat Keliling 01',
            '2026-10-19 Loket Kantor Lurah Oebobo', '2026-10-19 Samsat Corner',
            '2026-10-20 Loket Kantor Lurah Oebobo', '2026-10-20 Samsat Corner',
            '2026-10-21 Loket Kantor Lurah Oebobo', '2026-10-21 Samsat Corner',
            '2026-10-22 Loket Kantor Lurah Oebobo', '2026-10-22 Samsat Corner',
            '2026-10-23 Loket Kantor Lurah Oebobo', '2026-10-23 Samsat Corner',
            '2026-10-25 Samsat Keliling 03',
        ], array_map(fn (array $served): string => "{$served['tanggal']} {$served['nama']}", $response->json('jadwal')));
        $this->assertSame(['Loket Kantor Lurah Oebobo', 'Loket Pameran Pembangunan'], $response->json('sementara.*.nama'));
        $this->assertSame(['2026-11-10', '2026-11-15', [0, 1, 2, 3, 4, 5, 6]], [
            $response->json('sementara.1.tanggalMulai'),
            $response->json('sementara.1.tanggalSelesai'),
            $response->json('sementara.1.hari'),
        ]);
    }

    public function test_lists_samsat_keliling_then_counters_then_outlets_at_the_same_time(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 10:00:00', 'Asia/Makassar'));
        $times = ['mulai' => '08:00', 'selesai' => '12:00'];
        MobileServiceStop::factory()->outlet('A Gerai', [5])->create($times);
        MobileServiceStop::factory()->counter('2026-10-16', '2026-10-16', 'B Loket', [5])->create($times);
        MobileServiceStop::factory()->on('2026-10-16', '03')->create($times);

        $response = $this->getJson('/api/public/keliling');

        $this->assertSame(
            ['Samsat Keliling 03', 'B Loket', 'A Gerai'],
            array_column(array_filter($response->json('jadwal'), fn (array $served): bool => $served['tanggal'] === '2026-10-16'), 'nama'),
        );
    }

    #[TestWith(['2026-10-18 23:30:00', '2026-10-18', '2026-10-25'])]
    #[TestWith(['2026-10-19 00:30:00', '2026-10-19', '2026-11-01'])]
    #[TestWith(['2026-10-24 12:00:00', '2026-10-24', '2026-11-01'])]
    public function test_counts_the_days_in_kupang_time(string $now, string $from, string $until): void
    {
        $this->travelTo(Carbon::parse($now, 'Asia/Makassar'));

        $this->getJson('/api/public/keliling')->assertOk()->assertJsonPath('dari', $from)->assertJsonPath('sampai', $until);
    }

    public function test_returns_empty_lists_when_nothing_is_active(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 10:00:00', 'Asia/Makassar'));
        MobileServiceStop::factory()->inactive()->create();

        $this->getJson('/api/public/keliling')->assertOk()->assertExactJson([
            'dari' => '2026-10-14',
            'sampai' => '2026-10-25',
            'jadwal' => [],
            'gerai' => [],
            'sementara' => [],
        ]);
    }

    public function test_starts_no_session_and_forbids_caching(): void
    {
        $response = $this->withHeader('Referer', config('app.url').'/')->getJson('/api/public/keliling');

        $response->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertCookieMissing(config('session.cookie'))
            ->assertCookieMissing('XSRF-TOKEN');
    }

    public function test_rejects_query_parameters_with_422(): void
    {
        $response = $this->get('/api/public/keliling?hari=1');

        $response->assertUnprocessable()->assertJsonValidationErrors(['query' => 'Parameter kueri tidak didukung.']);
    }

    public function test_returns_503_instead_of_an_empty_schedule_when_the_source_cannot_be_read(): void
    {
        DB::table('mobile_service_stops')->exists();
        DB::beforeExecuting(function (string $query): void {
            if (preg_match('/\bmobile_service_stops\b/', $query) === 1) {
                throw new QueryException(DB::getName(), $query, [], new PDOException('SQLSTATE[HY000] [2002] Connection refused'));
            }
        });
        Exceptions::fake();

        $response = $this->getJson('/api/public/keliling');

        $response->assertServiceUnavailable()->assertExactJson(['message' => 'Konten sedang tidak dapat ditampilkan. Silakan coba lagi nanti.']);
        Exceptions::assertReported(PublicContentUnavailableException::class);
    }

    public function test_shares_the_public_api_limit(): void
    {
        config(['pintu.public_api.requests_per_minute' => 2]);
        $this->getJson('/api/public/faq')->assertOk();
        $this->getJson('/api/public/keliling')->assertOk();

        $this->getJson('/api/public/keliling')->assertTooManyRequests();
    }
}
