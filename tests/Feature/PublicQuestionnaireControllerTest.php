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

/**
 * Which questionnaires at /kuesioner take answers, for the portal (D-098).
 */
class PublicQuestionnaireControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_shows_both_questionnaires_open_until_first_saved(): void
    {
        $this->getJson('/api/public/kuesioner')
            ->assertOk()
            ->assertExactJson(['wajibPajak' => ['dibuka' => true], 'operator' => ['dibuka' => true]]);
    }

    public function test_publishes_only_whether_each_one_is_open(): void
    {
        Setting::query()->forceCreate(['name' => Setting::QUESTIONNAIRE, 'value' => [
            'wajibPajak' => ['dibuka' => false, 'sejak' => '2026-11-20T06:15:00+00:00'],
            'operator' => ['dibuka' => true, 'sejak' => '2026-11-21T01:00:00+00:00'],
        ]]);

        $this->getJson('/api/public/kuesioner')
            ->assertOk()
            ->assertExactJson(['wajibPajak' => ['dibuka' => false], 'operator' => ['dibuka' => true]]);
    }

    public function test_starts_no_session_forbids_caching_and_rejects_query_parameters(): void
    {
        $this->withHeader('Referer', config('app.url').'/')->getJson('/api/public/kuesioner')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertCookieMissing(config('session.cookie'));
        $this->get('/api/public/kuesioner?jenis=operator')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['query' => 'Parameter kueri tidak didukung.']);
        $this->putJson('/api/public/kuesioner', ['operator' => ['dibuka' => false]])->assertMethodNotAllowed();
        $this->assertDatabaseCount('settings', 0);
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

        $this->getJson('/api/public/kuesioner')->assertServiceUnavailable();

        Exceptions::assertReported(PublicContentUnavailableException::class);
    }
}
