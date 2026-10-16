<?php

namespace Tests\Feature;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\AgencyApp;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use PDOException;
use Tests\TestCase;

class PublicAgencyAppControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_lists_the_apps_in_order_with_full_logo_addresses_and_without_ids(): void
    {
        $app = AgencyApp::factory()->android()->create([
            'nama' => 'SIMPEL',
            'nama_lengkap' => 'Sistem Informasi Pelayanan',
            'keterangan' => 'Pencatatan pelayanan loket.',
            'tautan' => 'https://play.google.com/store/apps/details?id=id.go.ntt.simpel',
            'logo' => 'https://bucket.test/aplikasi/2026/10/simpel.png',
            'urutan' => 0,
        ]);

        $response = $this->getJson('/api/public/aplikasi');

        $response->assertOk()
            ->assertJsonPath('aplikasi.*.nama', ['PINTU', 'SIMPEL', 'PRO NTT', 'SIKAP', 'SIPAS', 'Tilang / Razia'])
            ->assertJsonPath('aplikasi.0.logo', asset('assets/img/aplikasi/pintu-240.webp'))
            ->assertJsonPath('aplikasi.1', [
                'nama' => 'SIMPEL',
                'namaLengkap' => 'Sistem Informasi Pelayanan',
                'keterangan' => 'Pencatatan pelayanan loket.',
                'jenis' => 'android',
                'pengelola' => 'bpad',
                'tautan' => 'https://play.google.com/store/apps/details?id=id.go.ntt.simpel',
                'logo' => 'https://bucket.test/aplikasi/2026/10/simpel.png',
            ]);
        $this->assertStringNotContainsString($app->id, $response->getContent());
    }

    public function test_starts_no_session_forbids_caching_and_rejects_query_parameters(): void
    {
        $this->withHeader('Referer', config('app.url').'/')->getJson('/api/public/aplikasi')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertCookieMissing(config('session.cookie'));
        $this->get('/api/public/aplikasi?jenis=android')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['query' => 'Parameter kueri tidak didukung.']);
    }

    public function test_returns_503_when_the_source_cannot_be_read(): void
    {
        DB::table('agency_apps')->exists();
        DB::beforeExecuting(function (string $query): void {
            if (preg_match('/\bagency_apps\b/', $query) === 1) {
                throw new QueryException(DB::getName(), $query, [], new PDOException('SQLSTATE[HY000] [2002] Connection refused'));
            }
        });
        Exceptions::fake();

        $this->getJson('/api/public/aplikasi')->assertServiceUnavailable();

        Exceptions::assertReported(PublicContentUnavailableException::class);
    }
}
