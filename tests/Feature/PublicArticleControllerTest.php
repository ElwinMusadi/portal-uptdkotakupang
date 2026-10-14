<?php

namespace Tests\Feature;

use App\Enums\WorkflowStatus;
use App\Exceptions\PublicContentUnavailableException;
use App\Models\Article;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use PDOException;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PublicArticleControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_lists_nothing_while_the_launch_article_is_a_draft(): void
    {
        $this->getJson('/api/public/berita')->assertOk()->assertExactJson([]);
        $this->getJson('/api/public/berita/pintu-resmi-diluncurkan')->assertNotFound()->assertExactJson(['message' => 'Data tidak ditemukan.']);
    }

    public function test_lists_only_published_articles_newest_first_without_their_bodies(): void
    {
        Article::query()->delete();
        foreach ([WorkflowStatus::Draft, WorkflowStatus::UnderReview, WorkflowStatus::AwaitingApproval, WorkflowStatus::Returned, WorkflowStatus::Archived] as $status) {
            Article::factory()->withStatus($status)->create(['tanggal' => '2026-12-01']);
        }
        $this->travelTo(Carbon::parse('2026-10-14 01:00:00', 'UTC'));
        Article::factory()->withStatus(WorkflowStatus::Published)->create(['slug' => 'pagi', 'tanggal' => '2026-10-14', 'isi' => str_repeat('<p>kata</p>', 300)]);
        $this->travel(3)->hours();
        // Published last, but dated earlier
        Article::factory()->withStatus(WorkflowStatus::Published)->create(['slug' => 'lama', 'tanggal' => '2026-10-01']);
        $this->travel(-1)->hours();
        Article::factory()->withStatus(WorkflowStatus::Published)->create([
            'judul' => 'Loket buka Sabtu.',
            'slug' => 'siang',
            'kategori' => 'pengumuman',
            'ringkasan' => 'Khusus pajak tahunan.',
            'sampul' => 'https://berkas.example/berita/sabtu.jpg',
            'keterangan_sampul' => 'Loket pajak tahunan.',
            'tanggal' => '2026-10-14',
            'sorotan' => true,
            'pengumuman_beranda' => true,
        ]);

        $response = $this->getJson('/api/public/berita');

        $response->assertOk()
            ->assertJsonPath('*.slug', ['siang', 'pagi', 'lama'])
            ->assertJsonPath('1.menitBaca', 2)
            ->assertJsonPath('0', [
                'judul' => 'Loket buka Sabtu.',
                'slug' => 'siang',
                'kategori' => 'pengumuman',
                'ringkasan' => 'Khusus pajak tahunan.',
                'sampul' => 'https://berkas.example/berita/sabtu.jpg',
                'keteranganSampul' => 'Loket pajak tahunan.',
                'tanggal' => '2026-10-14',
                'sorotan' => true,
                'pengumumanBeranda' => true,
                'menitBaca' => 1,
                'terbitPada' => '2026-10-14T03:00:00+00:00',
            ]);
    }

    public function test_shows_a_published_article_with_its_body_but_without_its_id_or_history(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 01:00:00', 'UTC'));
        Article::factory()->withStatus(WorkflowStatus::Published)->create([
            'judul' => 'Lima berkas yang sering terlupa.',
            'slug' => 'lima-berkas',
            'kategori' => 'edukasi',
            'ringkasan' => 'Cek sebelum berangkat.',
            'isi' => '<p>'.str_repeat('kata ', 450).'</p><h2>Daftar.</h2>',
            'kredit_foto' => 'Seksi Verifikasi',
            'tanggal' => '2026-10-14',
            'pemeriksa' => 'Kepala Seksi Verifikasi',
            'riwayat' => [['waktu' => '2026-10-14T01:00:00+00:00', 'oleh' => 'Maria', 'aksi' => 'menyetujui dan menerbitkan', 'ke' => 'terbit']],
        ]);

        $this->getJson('/api/public/berita/lima-berkas')
            ->assertOk()
            ->assertExactJson([
                'judul' => 'Lima berkas yang sering terlupa.',
                'slug' => 'lima-berkas',
                'kategori' => 'edukasi',
                'ringkasan' => 'Cek sebelum berangkat.',
                'isi' => '<p>'.str_repeat('kata ', 450).'</p><h2>Daftar.</h2>',
                'sampul' => null,
                'keteranganSampul' => '',
                'kreditFoto' => 'Seksi Verifikasi',
                'tanggal' => '2026-10-14',
                'pemeriksa' => 'Kepala Seksi Verifikasi',
                'menitBaca' => 2,
                'terbitPada' => '2026-10-14T01:00:00+00:00',
                'diperbarui' => '2026-10-14T01:00:00+00:00',
            ]);
    }

    #[TestWith([WorkflowStatus::Draft])]
    #[TestWith([WorkflowStatus::AwaitingApproval])]
    #[TestWith([WorkflowStatus::Archived])]
    public function test_does_not_find_articles_that_are_not_published(WorkflowStatus $status): void
    {
        Article::factory()->withStatus($status)->create(['slug' => 'belum-terbit']);

        $this->getJson('/api/public/berita/belum-terbit')->assertNotFound()->assertExactJson(['message' => 'Data tidak ditemukan.']);
    }

    #[TestWith(['/api/public/berita'])]
    #[TestWith(['/api/public/berita/pintu-resmi-diluncurkan'])]
    public function test_starts_no_session_forbids_caching_and_rejects_query_parameters(string $uri): void
    {
        Article::query()->update(['status' => WorkflowStatus::Published->value]);

        $this->withHeader('Referer', config('app.url').'/')->getJson($uri)
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertCookieMissing(config('session.cookie'));
        $this->get("{$uri}?kategori=berita")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['query' => 'Parameter kueri tidak didukung.']);
    }

    #[TestWith(['/api/public/berita'])]
    #[TestWith(['/api/public/berita/pintu-resmi-diluncurkan'])]
    public function test_returns_503_when_the_source_cannot_be_read(string $uri): void
    {
        DB::table('articles')->exists();
        DB::beforeExecuting(function (string $query): void {
            if (preg_match('/\barticles\b/', $query) === 1) {
                throw new QueryException(DB::getName(), $query, [], new PDOException('SQLSTATE[HY000] [2002] Connection refused'));
            }
        });
        Exceptions::fake();

        $this->getJson($uri)->assertServiceUnavailable();

        Exceptions::assertReported(PublicContentUnavailableException::class);
    }
}
