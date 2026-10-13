<?php

namespace Tests\Feature;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\ScheduleAdjustment;
use App\Models\Setting;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use PDOException;
use Tests\TestCase;

class PublicOpeningHoursControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_no_hours_until_they_are_first_saved(): void
    {
        $response = $this->getJson('/api/public/jam');

        $response->assertOk()->assertExactJson([
            'hari' => [],
            'batasBerkasMenit' => 0,
            'catatan' => '',
            'pemberitahuan' => '',
            'penyesuaian' => [],
        ]);
    }

    public function test_returns_the_saved_hours_and_the_adjustments_from_today_in_kupang(): void
    {
        $hours = [
            'hari' => [['hari' => 1, 'buka' => true, 'mulai' => '08:00', 'selesai' => '15:00', 'catatan' => '']],
            'batasBerkasMenit' => 60,
            'catatan' => 'Datang pagi.',
            'pemberitahuan' => 'Diumumkan H-3.',
        ];
        (new Setting)->forceFill(['name' => Setting::OPENING_HOURS, 'value' => $hours])->save();
        // 23:30 UTC on 13 October is 07:30 WITA on 14 October.
        $this->travelTo(Carbon::parse('2026-10-13 23:30:00', 'UTC'));
        ScheduleAdjustment::factory()->create(['tanggal' => '2026-10-13', 'keterangan' => 'Kemarin di Kupang']);
        ScheduleAdjustment::factory()->create(['tanggal' => '2026-12-25', 'keterangan' => 'Hari Raya Natal']);
        ScheduleAdjustment::factory()->specialHours('08:00', '11:30')->create(['tanggal' => '2026-10-14', 'keterangan' => 'Apel bersama']);

        $response = $this->getJson('/api/public/jam');

        $response->assertOk()->assertExactJson($hours + [
            'penyesuaian' => [
                ['tanggal' => '2026-10-14', 'jenis' => 'jam-khusus', 'mulai' => '08:00', 'selesai' => '11:30', 'keterangan' => 'Apel bersama'],
                ['tanggal' => '2026-12-25', 'jenis' => 'tutup', 'mulai' => '', 'selesai' => '', 'keterangan' => 'Hari Raya Natal'],
            ],
        ]);
        $this->assertSame(['2026-10-14', '2026-12-25'], $response->json('penyesuaian.*.tanggal'));
    }

    public function test_starts_no_session_forbids_caching_and_rejects_query_parameters(): void
    {
        $this->withHeader('Referer', config('app.url').'/')->getJson('/api/public/jam')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertCookieMissing(config('session.cookie'));
        $this->get('/api/public/jam?hari=1')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['query' => 'Parameter kueri tidak didukung.']);
    }

    public function test_returns_503_instead_of_guessing_when_the_source_cannot_be_read(): void
    {
        DB::table('settings')->exists();
        DB::beforeExecuting(function (string $query): void {
            if (preg_match('/\bsettings\b/', $query) === 1) {
                throw new QueryException(DB::getName(), $query, [], new PDOException('SQLSTATE[HY000] [2002] Connection refused'));
            }
        });
        Exceptions::fake();

        $response = $this->getJson('/api/public/jam');

        $response->assertServiceUnavailable();
        Exceptions::assertReported(PublicContentUnavailableException::class);
    }
}
