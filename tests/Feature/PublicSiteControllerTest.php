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

class PublicSiteControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_shows_the_footer_and_announcement_in_the_markup_without_a_time_until_first_saved(): void
    {
        $this->getJson('/api/public/situs')
            ->assertOk()
            ->assertExactJson([
                'deskripsi' => Setting::INITIAL_SITE['deskripsi'],
                'hakCipta' => Setting::INITIAL_SITE['hakCipta'],
                'bilahPengumuman' => Setting::INITIAL_SITE['bilahPengumuman'],
                'diperbarui' => null,
            ]);
    }

    public function test_publishes_only_the_footer_and_announcement_of_the_saved_document(): void
    {
        $site = Setting::INITIAL_SITE;
        $site['deskripsi'] = 'Deskripsi baru.';
        $site['urlPortal'] = 'https://intra.example.go.id/';
        $site['bilahPengumuman']['aktif'] = false;
        $setting = Setting::query()->forceCreate(['name' => Setting::SITE, 'value' => $site]);

        $response = $this->getJson('/api/public/situs');

        $response->assertOk()
            ->assertJsonPath('deskripsi', 'Deskripsi baru.')
            ->assertJsonPath('bilahPengumuman.aktif', false)
            ->assertJsonPath('diperbarui', $setting->updated_at->toIso8601String());
        $this->assertSame(['deskripsi', 'hakCipta', 'bilahPengumuman', 'diperbarui'], array_keys($response->json()));
    }

    public function test_starts_no_session_forbids_caching_and_rejects_query_parameters(): void
    {
        $this->withHeader('Referer', config('app.url').'/')->getJson('/api/public/situs')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertCookieMissing(config('session.cookie'));
        $this->get('/api/public/situs?bagian=kaki')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['query' => 'Parameter kueri tidak didukung.']);
        $this->putJson('/api/public/situs', Setting::INITIAL_SITE)->assertMethodNotAllowed();
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

        $this->getJson('/api/public/situs')->assertServiceUnavailable();

        Exceptions::assertReported(PublicContentUnavailableException::class);
    }
}
