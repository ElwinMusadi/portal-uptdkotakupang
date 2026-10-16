<?php

namespace Tests\Feature;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\MobileServiceStop;
use App\Models\ServiceBoardOrder;
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
                'urutanPapan' => null,
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

    public function test_numbers_each_dates_board_order_without_changing_the_time_order(): void
    {
        // Wednesday 14 October 2026 in Kupang
        $this->travelTo(Carbon::parse('2026-10-14 10:00:00', 'Asia/Makassar'));
        $outlet = MobileServiceStop::factory()->outlet('Mall Pelayanan Publik', [1, 2, 3, 4, 5])->create(['mulai' => '08:30', 'lokasi' => 'MPP']);
        $first = MobileServiceStop::factory()->on('2026-10-15', '01')->create(['mulai' => '09:00', 'lokasi' => 'Bundaran PU']);
        $second = MobileServiceStop::factory()->on('2026-10-15', '02')->create(['mulai' => '10:00', 'lokasi' => 'San Pedro']);
        $off = MobileServiceStop::factory()->on('2026-10-15', '03')->inactive()->create(['mulai' => '07:00', 'lokasi' => 'Belum aktif']);
        // Thursday: the switched-off stop leaves no gap and the unlisted stop stays unnumbered
        ServiceBoardOrder::factory()->of('2026-10-15', [$off, $second, $outlet])->create();
        // Friday: listed entries that are not served that day are passed over
        ServiceBoardOrder::factory()->of('2026-10-16', [$first, $second, $outlet])->create();
        // Yesterday's order is no longer read
        ServiceBoardOrder::factory()->of('2026-10-13', [$outlet])->create();

        $response = $this->getJson('/api/public/keliling');

        $response->assertOk();
        $this->assertSame([
            '2026-10-14 MPP -',
            '2026-10-15 MPP 2', '2026-10-15 Bundaran PU -', '2026-10-15 San Pedro 1',
            '2026-10-16 MPP 1',
            '2026-10-19 MPP -',
        ], array_slice(array_map(
            fn (array $served): string => "{$served['tanggal']} {$served['lokasi']} ".($served['urutanPapan'] ?? '-'),
            $response->json('jadwal'),
        ), 0, 6));
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

    #[TestWith(['mobile_service_stops'])]
    #[TestWith(['service_board_orders'])]
    public function test_returns_503_instead_of_an_empty_schedule_when_the_source_cannot_be_read(string $table): void
    {
        DB::table($table)->exists();
        DB::beforeExecuting(function (string $query) use ($table): void {
            if (preg_match("/\b{$table}\b/", $query) === 1) {
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
