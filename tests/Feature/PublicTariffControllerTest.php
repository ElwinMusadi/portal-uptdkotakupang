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

class PublicTariffControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_zero_rates_without_a_time_until_the_tariff_is_first_saved(): void
    {
        $response = $this->getJson('/api/public/tarif');

        $response->assertOk()->assertExactJson(Setting::UNSAVED_TARIFF + ['diperbarui' => null]);
    }

    public function test_returns_the_saved_tariff_with_the_time_it_was_saved(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 08:00:00', 'UTC'));
        $tariff = [
            'opsenPersen' => 66,
            'dendaPersenPerBulan' => 1.5,
            'maksBulanDenda' => 24,
            'swdklljMotor' => 35000,
            'swdklljMobil' => 143000,
            'contohPokok' => 285000,
            'contohJenis' => 'mobil',
            'contohTelat' => 3,
            'catatan' => 'Tagihan resmi mengikuti notis pajak di loket.',
        ];
        (new Setting)->forceFill(['name' => Setting::TARIFF, 'value' => $tariff])->save();

        $response = $this->getJson('/api/public/tarif');

        $response->assertOk()->assertExactJson($tariff + ['diperbarui' => '2026-10-13T08:00:00+00:00']);
    }

    public function test_starts_no_session_forbids_caching_and_rejects_query_parameters(): void
    {
        $this->withHeader('Referer', config('app.url').'/')->getJson('/api/public/tarif')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertCookieMissing(config('session.cookie'));
        $this->get('/api/public/tarif?jenis=mobil')
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

        $this->getJson('/api/public/tarif')->assertServiceUnavailable();

        Exceptions::assertReported(PublicContentUnavailableException::class);
    }
}
