<?php

namespace Tests\Feature;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\StaticPage;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use PDOException;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PublicStaticPageControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_shows_a_page_that_was_never_saved_without_a_time_so_the_portal_keeps_its_markup(): void
    {
        $response = $this->getJson('/api/public/halaman/syarat-ketentuan');

        $response->assertOk()->assertExactJson([
            'judul' => 'Informasi resmi.',
            'judulLanjutan' => 'Dipakai dengan bijak.',
            'pengantar' => 'Syarat & ketentuan · berlaku sejak [tanggal terbit]',
            'isi' => StaticPage::query()->where('slug', 'syarat-ketentuan')->value('isi'),
            'diperbarui' => null,
        ]);
        $this->assertStringStartsWith('<h2>Sifat informasi.</h2>', $response->json('isi'));
    }

    #[TestWith(['kebijakan-privasi'])]
    #[TestWith(['syarat-ketentuan'])]
    public function test_starts_with_the_text_the_portal_page_shows_in_its_markup(string $slug): void
    {
        $page = $this->getJson("/api/public/halaman/{$slug}")->assertOk()->json();
        $html = file_get_contents(resource_path("views/portal/{$slug}.blade.php"));
        $text = fn (string $html): string => trim(preg_replace('/\s+/u', ' ', html_entity_decode(preg_replace('/<[^>]+>/', ' ', $html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        $this->assertSame(1, preg_match('/<p class="eyebrow" data-halaman-pengantar>(.*?)<\/p>/', $html, $intro));
        $this->assertSame(1, preg_match('/<h1 class="h-display" data-halaman-judul>(.*?)<\/h1>/', $html, $title));
        $this->assertSame(1, preg_match('/<div class="container container--narrow prose" data-halaman="'.$slug.'">(.*?)<\/div>/s', $html, $content));
        $this->assertSame($page['pengantar'], $text($intro[1]));
        $this->assertSame("{$page['judul']} {$page['judulLanjutan']}", $text($title[1]));
        $this->assertSame($text($page['isi']), $text($content[1]));
    }

    public function test_shows_the_saved_page_with_the_time_it_was_saved(): void
    {
        $page = StaticPage::query()->where('slug', 'kebijakan-privasi')->firstOrFail();
        $page->update(['judul' => 'Data Anda aman.', 'isi' => '<p>Isi baru.</p>']);

        $this->getJson('/api/public/halaman/kebijakan-privasi')
            ->assertOk()
            ->assertJsonPath('judul', 'Data Anda aman.')
            ->assertJsonPath('isi', '<p>Isi baru.</p>')
            ->assertJsonPath('diperbarui', $page->fresh()->updated_at->toIso8601String());
    }

    public function test_answers_404_for_other_slugs_and_missing_pages(): void
    {
        StaticPage::factory()->create(['slug' => 'halaman-lain']);

        $this->getJson('/api/public/halaman/halaman-lain')->assertNotFound();
        $this->getJson('/api/public/halaman/beranda')->assertNotFound();
        StaticPage::query()->where('slug', 'kebijakan-privasi')->delete();

        $this->getJson('/api/public/halaman/kebijakan-privasi')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Data tidak ditemukan.']);
    }

    public function test_starts_no_session_forbids_caching_and_rejects_query_parameters(): void
    {
        $this->withHeader('Referer', config('app.url').'/')->getJson('/api/public/halaman/kebijakan-privasi')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertCookieMissing(config('session.cookie'));
        $this->get('/api/public/halaman/kebijakan-privasi?pratinjau=1')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['query' => 'Parameter kueri tidak didukung.']);
        $this->putJson('/api/public/halaman/kebijakan-privasi', ['judul' => 'x'])->assertMethodNotAllowed();
    }

    public function test_returns_503_when_the_source_cannot_be_read(): void
    {
        DB::table('static_pages')->exists();
        DB::beforeExecuting(function (string $query): void {
            if (preg_match('/\bstatic_pages\b/', $query) === 1) {
                throw new QueryException(DB::getName(), $query, [], new PDOException('SQLSTATE[HY000] [2002] Connection refused'));
            }
        });
        Exceptions::fake();

        $this->getJson('/api/public/halaman/kebijakan-privasi')->assertServiceUnavailable();

        Exceptions::assertReported(PublicContentUnavailableException::class);
    }
}
