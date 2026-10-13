<?php

namespace Tests\Feature;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\MobileServiceStop;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use PDOException;
use Tests\TestCase;

class PublicMobileServiceStopControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_active_stops_by_day_and_time_without_internal_fields(): void
    {
        MobileServiceStop::factory()->create(['hari' => 2, 'mulai' => '08:00', 'lokasi' => 'Selasa pagi']);
        MobileServiceStop::factory()->inactive()->create(['hari' => 1, 'mulai' => '07:00', 'lokasi' => 'Tidak aktif']);
        MobileServiceStop::factory()->create(['hari' => 1, 'mulai' => '13:00', 'selesai' => '15:00', 'lokasi' => 'Senin siang']);
        MobileServiceStop::factory()->create([
            'hari' => 1,
            'mulai' => '08:00',
            'selesai' => '12:00',
            'lokasi' => 'Kantor Kelurahan Oebobo',
            'kecamatan' => 'Kec. Oebobo',
            'layanan' => 'Pajak tahunan',
            'catatan' => 'Bawa STNK, notis, dan KTP asli.',
        ]);

        $response = $this->getJson('/api/public/keliling');

        $response->assertOk()->assertJsonPath('0', [
            'hari' => 1,
            'mulai' => '08:00',
            'selesai' => '12:00',
            'lokasi' => 'Kantor Kelurahan Oebobo',
            'kecamatan' => 'Kec. Oebobo',
            'layanan' => 'Pajak tahunan',
            'catatan' => 'Bawa STNK, notis, dan KTP asli.',
        ]);
        $this->assertSame(['Kantor Kelurahan Oebobo', 'Senin siang', 'Selasa pagi'], $response->json('*.lokasi'));
    }

    public function test_returns_an_empty_array_when_no_stop_is_active(): void
    {
        MobileServiceStop::factory()->inactive()->create();

        $this->getJson('/api/public/keliling')->assertOk()->assertExactJson([]);
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
