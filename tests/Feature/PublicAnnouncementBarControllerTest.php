<?php

namespace Tests\Feature;

use App\Enums\WorkflowStatus;
use App\Exceptions\PublicContentUnavailableException;
use App\Models\Article;
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
            ->assertExactJson([
                'aktif' => true,
                'beranda' => false,
                'kategori' => 'pengumuman',
                'teks' => Setting::INITIAL_ANNOUNCEMENT_BAR['teks'],
                'tautanLabel' => 'Baca',
                'tautanUrl' => 'berita-detail.html',
                'diperbarui' => null,
            ]);
    }

    public function test_returns_the_saved_bar_with_its_time(): void
    {
        $this->travelTo(Carbon::parse('2026-10-25 13:00', 'UTC'));
        $this->saveBar(['aktif' => false, 'beranda' => true, 'sumber' => 'teks', 'tulisan' => null, 'teks' => 'Loket tutup Senin.', 'tautanLabel' => '', 'tautanUrl' => '']);

        $this->getJson('/api/public/pengumuman')
            ->assertOk()
            ->assertExactJson(['aktif' => false, 'beranda' => true, 'kategori' => 'pengumuman', 'teks' => 'Loket tutup Senin.', 'tautanLabel' => '', 'tautanUrl' => '', 'diperbarui' => '2026-10-25T13:00:00+00:00']);
    }

    public function test_returns_a_bar_saved_before_d083_on_informasi_only(): void
    {
        $this->saveBar(['aktif' => true, 'teks' => 'Loket tutup Senin.', 'tautanLabel' => 'Lihat', 'tautanUrl' => 'jadwal.html']);

        $this->getJson('/api/public/pengumuman')
            ->assertOk()
            ->assertJson(['aktif' => true, 'beranda' => false, 'kategori' => 'pengumuman', 'teks' => 'Loket tutup Senin.', 'tautanLabel' => 'Lihat', 'tautanUrl' => 'jadwal.html']);
    }

    public function test_takes_the_title_category_and_address_of_the_chosen_published_tulisan(): void
    {
        $article = Article::factory()->withStatus(WorkflowStatus::Published)->create(['judul' => 'Samsat Keliling di Oebobo', 'slug' => 'samsat-keliling-di-oebobo', 'kategori' => 'keliling']);
        $this->saveBar(['aktif' => true, 'beranda' => true, 'sumber' => 'tulisan', 'tulisan' => $article->id, 'teks' => 'Teks yang ditulis sendiri.', 'tautanLabel' => 'Baca', 'tautanUrl' => 'jadwal.html']);

        $this->getJson('/api/public/pengumuman')
            ->assertOk()
            ->assertJson(['aktif' => true, 'beranda' => true, 'kategori' => 'keliling', 'teks' => 'Samsat Keliling di Oebobo', 'tautanLabel' => 'Baca', 'tautanUrl' => '/berita/samsat-keliling-di-oebobo'])
            ->assertJsonMissingPath('sumber')
            ->assertJsonMissingPath('tulisan');
    }

    public function test_keeps_a_switched_off_bar_hidden_though_its_tulisan_is_published(): void
    {
        $article = Article::factory()->withStatus(WorkflowStatus::Published)->create();
        $this->saveBar(['aktif' => false, 'beranda' => true, 'sumber' => 'tulisan', 'tulisan' => $article->id, 'teks' => '', 'tautanLabel' => '', 'tautanUrl' => '']);

        $this->getJson('/api/public/pengumuman')->assertOk()->assertJsonPath('aktif', false);
    }

    public function test_hides_the_bar_while_its_tulisan_is_not_published_or_deleted(): void
    {
        $article = Article::factory()->withStatus(WorkflowStatus::Published)->create();
        $this->saveBar(['aktif' => true, 'beranda' => true, 'sumber' => 'tulisan', 'tulisan' => $article->id, 'teks' => 'Teks yang ditulis sendiri.', 'tautanLabel' => 'Baca', 'tautanUrl' => 'jadwal.html']);
        $hidden = ['aktif' => false, 'beranda' => true, 'kategori' => 'pengumuman', 'teks' => '', 'tautanLabel' => 'Baca', 'tautanUrl' => ''];

        $article->forceFill(['status' => WorkflowStatus::Archived])->save();
        $this->getJson('/api/public/pengumuman')->assertOk()->assertJson($hidden);

        $article->delete();
        $this->getJson('/api/public/pengumuman')->assertOk()->assertJson($hidden);
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

    public function test_returns_503_when_the_tulisan_cannot_be_read(): void
    {
        $this->saveBar([...Setting::INITIAL_ANNOUNCEMENT_BAR, 'sumber' => 'tulisan', 'tulisan' => '01kq0000000000000000000000']);
        DB::beforeExecuting(function (string $query): void {
            if (preg_match('/\barticles\b/', $query) === 1) {
                throw new QueryException(DB::getName(), $query, [], new PDOException('SQLSTATE[HY000] [2002] Connection refused'));
            }
        });
        Exceptions::fake();

        $this->getJson('/api/public/pengumuman')->assertServiceUnavailable();

        Exceptions::assertReported(PublicContentUnavailableException::class);
    }

    public function test_reads_no_tulisan_for_a_written_bar(): void
    {
        $this->saveBar(Setting::INITIAL_ANNOUNCEMENT_BAR);
        DB::beforeExecuting(function (string $query): void {
            if (preg_match('/\barticles\b/', $query) === 1) {
                throw new QueryException(DB::getName(), $query, [], new PDOException('SQLSTATE[HY000] [2002] Connection refused'));
            }
        });

        $this->getJson('/api/public/pengumuman')->assertOk()->assertJsonPath('teks', Setting::INITIAL_ANNOUNCEMENT_BAR['teks']);
    }

    /**
     * @param  array<string, mixed>  $bar
     */
    private function saveBar(array $bar): void
    {
        (new Setting)->forceFill(['name' => Setting::ANNOUNCEMENT_BAR, 'value' => $bar])->save();
    }
}
