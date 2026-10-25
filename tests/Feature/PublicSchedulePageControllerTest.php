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

class PublicSchedulePageControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_the_markup_text_without_a_time_until_first_saved(): void
    {
        $this->getJson('/api/public/jadwal')
            ->assertOk()
            ->assertExactJson([...Setting::INITIAL_SCHEDULE_PAGE, 'diperbarui' => null]);
    }

    public function test_returns_the_saved_document_with_its_time(): void
    {
        $this->travelTo(Carbon::parse('2026-10-25 09:30', 'UTC'));
        $page = Setting::INITIAL_SCHEDULE_PAGE;
        $page['luarKantor']['catatan'] = '';
        $page['luarKantor']['judul'] = 'Kami datang ke Anda.';
        (new Setting)->forceFill(['name' => Setting::SCHEDULE_PAGE, 'value' => $page])->save();

        $this->getJson('/api/public/jadwal')
            ->assertOk()
            ->assertExactJson([...$page, 'diperbarui' => '2026-10-25T09:30:00+00:00']);
    }

    public function test_starts_no_session_forbids_caching_and_rejects_query_parameters(): void
    {
        $this->withHeader('Referer', config('app.url').'/')->getJson('/api/public/jadwal')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertCookieMissing(config('session.cookie'));
        $this->get('/api/public/jadwal?bagian=jam')
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

        $this->getJson('/api/public/jadwal')->assertServiceUnavailable();

        Exceptions::assertReported(PublicContentUnavailableException::class);
    }
}
