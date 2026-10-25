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

class PublicAnnouncementBarControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_the_markup_bar_without_a_time_until_first_saved(): void
    {
        $this->getJson('/api/public/pengumuman')
            ->assertOk()
            ->assertExactJson([...Setting::INITIAL_ANNOUNCEMENT_BAR, 'diperbarui' => null]);
    }

    public function test_returns_the_saved_bar_with_its_time(): void
    {
        $this->travelTo(Carbon::parse('2026-10-25 13:00', 'UTC'));
        $bar = ['aktif' => false, 'teks' => 'Loket tutup Senin.', 'tautanLabel' => '', 'tautanUrl' => ''];
        (new Setting)->forceFill(['name' => Setting::ANNOUNCEMENT_BAR, 'value' => $bar])->save();

        $this->getJson('/api/public/pengumuman')
            ->assertOk()
            ->assertExactJson([...$bar, 'diperbarui' => '2026-10-25T13:00:00+00:00']);
    }

    public function test_starts_no_session_forbids_caching_and_rejects_query_parameters_and_writes(): void
    {
        $this->withHeader('Referer', config('app.url').'/')->getJson('/api/public/pengumuman')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertCookieMissing(config('session.cookie'));
        $this->get('/api/public/pengumuman?aktif=1')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['query' => 'Parameter kueri tidak didukung.']);
        $this->putJson('/api/public/pengumuman', Setting::INITIAL_ANNOUNCEMENT_BAR)->assertMethodNotAllowed();
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

        $this->getJson('/api/public/pengumuman')->assertServiceUnavailable();

        Exceptions::assertReported(PublicContentUnavailableException::class);
    }
}
