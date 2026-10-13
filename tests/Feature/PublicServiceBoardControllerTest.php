<?php

namespace Tests\Feature;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\Setting;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use PDOException;
use Tests\TestCase;

class PublicServiceBoardControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_an_empty_board_without_a_time_until_it_is_first_saved(): void
    {
        $response = $this->getJson('/api/public/papan');

        $response->assertOk()->assertJsonPath('diperbarui', null)->assertJsonPath('loket', [])->assertJsonPath('nomorAntrean', '');
    }

    public function test_returns_the_saved_board_without_the_counters_ids(): void
    {
        (new Setting)->forceFill(['name' => Setting::SERVICE_BOARD, 'value' => [
            'nomorAntrean' => 'A-042',
            'layananAntrean' => 'Loket 1 · Pajak tahunan',
            'waktuTunggu' => 18,
            'selisihKemarin' => -6,
            'loket' => [['id' => 'l1', 'kode' => 'L1', 'nama' => 'Pajak tahunan', 'status' => 'buka']],
            'catatan' => 'Diperbarui petugas setiap hari kerja.',
            'diperbarui' => '2026-10-13T23:45:00+00:00',
        ]])->save();

        $response = $this->getJson('/api/public/papan');

        $response->assertOk()->assertExactJson([
            'nomorAntrean' => 'A-042',
            'layananAntrean' => 'Loket 1 · Pajak tahunan',
            'waktuTunggu' => 18,
            'selisihKemarin' => -6,
            'loket' => [['kode' => 'L1', 'nama' => 'Pajak tahunan', 'status' => 'buka']],
            'catatan' => 'Diperbarui petugas setiap hari kerja.',
            'diperbarui' => '2026-10-13T23:45:00+00:00',
        ]);
    }

    public function test_starts_no_session_forbids_caching_and_rejects_query_parameters(): void
    {
        $this->withHeader('Referer', config('app.url').'/')->getJson('/api/public/papan')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertCookieMissing(config('session.cookie'));
        $this->get('/api/public/papan?loket=1')
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

        $this->getJson('/api/public/papan')->assertServiceUnavailable();

        Exceptions::assertReported(PublicContentUnavailableException::class);
    }
}
