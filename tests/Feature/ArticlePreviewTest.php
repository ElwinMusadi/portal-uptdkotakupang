<?php

namespace Tests\Feature;

use App\Actions\ShowPublicArticle;
use App\Enums\DashboardModule;
use App\Enums\WorkflowStatus;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ArticlePreviewTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-26 13:00', 'UTC'));
    }

    public function test_gives_a_preview_link_only_to_berita_managers(): void
    {
        $article = Article::factory()->create();

        $this->postJson("/api/koleksi/berita/{$article->id}/pratinjau")->assertUnauthorized();
        $this->actingAs(User::factory()->withModules(DashboardModule::Beranda)->create())
            ->postJson("/api/koleksi/berita/{$article->id}/pratinjau")
            ->assertForbidden();
        $this->actingAs($this->writer())->postJson('/api/koleksi/berita/01kq0000000000000000000000/pratinjau')->assertNotFound();

        $link = $this->link($article);

        $this->assertMatchesRegularExpression('#^'.preg_quote(url("/berita/pratinjau/{$article->id}"), '#').'\?expires=\d+&signature=[0-9a-f]{64}$#', $link['tautan']);
        $this->assertSame('2026-10-27T13:00:00+00:00', $link['berlaku']);
    }

    public function test_shows_the_saved_draft_on_the_portal_page_under_a_preview_band(): void
    {
        $article = Article::factory()->create(['judul' => 'Loket buka Sabtu']);
        $path = $this->path($this->link($article));
        $article->update(['judul' => 'Loket buka Sabtu, 31 Oktober 2026', 'ringkasan' => 'Diubah sesudah tautan dibuat.']);

        $response = $this->get($path)
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertHeader('Referrer-Policy', 'no-referrer');
        $html = $response->getContent();

        $this->assertSame([], $response->headers->getCookies());
        $this->assertStringContainsString("<head>\n  <meta name=\"robots\" content=\"noindex, nofollow\">\n  <meta name=\"referrer\" content=\"no-referrer\">", $html);
        $this->assertStringContainsString('<main id="main"><div class="pratinjau-pita" role="status"><div class="container"><p>Pratinjau · Draf, belum terbit. Tautan berlaku sampai 27 Okt 2026, 21.00 WITA.</p></div></div><script type="application/json" data-berita-pratinjau>', $html);
        $this->assertSame(ShowPublicArticle::present($article->fresh()), $this->previewData($html));
        $this->assertSame(1, substr_count($html, 'data-berita-pratinjau'));
        $this->assertStringContainsString('<link rel="stylesheet" href="/assets/css/tokens.css">', $html);
        $this->assertStringContainsString('<div data-berita-bagikan><dt>Bagikan</dt>', $html);
    }

    public function test_names_the_articles_status_and_keeps_its_text_inside_the_data(): void
    {
        $article = Article::factory()->withStatus(WorkflowStatus::Returned)->create([
            'judul' => 'Judul </script><script>alert(1)</script>',
            'ringkasan' => "Ringkasan & 'kutipan' \"ganda\"",
        ]);

        $html = $this->get($this->path($this->link($article)))->assertOk()->getContent();

        $this->assertStringContainsString('<p>Pratinjau · Dikembalikan, belum terbit.', $html);
        $this->assertStringNotContainsString('</script><script>alert(1)', $html);
        $hex = fn (string $character): string => chr(92).'u00'.strtoupper(dechex(ord($character)));
        $this->assertStringContainsString('Judul '.$hex('<').'/script'.$hex('>').$hex('<').'script'.$hex('>').'alert(1)', $html);
        $this->assertSame('Judul </script><script>alert(1)</script>', $this->previewData($html)['judul']);
        $this->assertSame("Ringkasan & 'kutipan' \"ganda\"", $this->previewData($html)['ringkasan']);
    }

    public function test_opens_for_anyone_with_the_link_on_any_host_for_24_hours(): void
    {
        $article = Article::factory()->create();
        $path = $this->path($this->link($article));

        $this->get('http://pintu.example.test'.$path)->assertOk();

        $this->travel(24)->hours();
        $this->get($path)->assertOk();

        $this->travel(1)->second();
        $html = $this->get($path)->assertForbidden()->getContent();
        $this->assertStringContainsString('<p>Tautan pratinjau tidak berlaku</p>', $html);
        $this->assertSame(['galat' => 'Tautan ini sudah kedaluwarsa atau tidak lengkap. Minta tautan pratinjau baru kepada pengelola berita.'], $this->previewData($html));
    }

    public function test_refuses_a_link_that_is_changed_or_incomplete(): void
    {
        $article = Article::factory()->create();
        $other = Article::factory()->create();
        $path = $this->path($this->link($article));

        $this->get(preg_replace_callback('/signature=([0-9a-f])/', fn (array $m): string => 'signature='.($m[1] === '0' ? '1' : '0'), $path))->assertForbidden();
        $this->get(str_replace($article->id, $other->id, $path))->assertForbidden();
        $this->get(preg_replace('/expires=\d+/', 'expires=9999999999', $path))->assertForbidden();
        $this->get("/berita/pratinjau/{$article->id}")->assertForbidden();
        $this->get('/berita/pratinjau/01kq0000000000000000000000')->assertNotFound();
    }

    public function test_sends_a_published_article_to_its_own_address(): void
    {
        $article = Article::factory()->create(['slug' => 'loket-buka-sabtu']);
        $path = $this->path($this->link($article));
        $article->forceFill(['status' => WorkflowStatus::Published])->save();

        $this->get($path)->assertRedirect('/berita/loket-buka-sabtu');
    }

    /**
     * @return array{tautan: string, berlaku: string}
     */
    private function link(Article $article): array
    {
        return $this->actingAs($this->writer())->postJson("/api/koleksi/berita/{$article->id}/pratinjau")
            ->assertCreated()
            ->json();
    }

    /**
     * @param  array{tautan: string, berlaku: string}  $link
     */
    private function path(array $link): string
    {
        return (string) parse_url($link['tautan'], PHP_URL_PATH).'?'.parse_url($link['tautan'], PHP_URL_QUERY);
    }

    /**
     * @return array<string, mixed>
     */
    private function previewData(string $html): array
    {
        $this->assertSame(1, preg_match('#<script type="application/json" data-berita-pratinjau>(.*?)</script>#s', $html, $match));

        return json_decode($match[1], true, flags: JSON_THROW_ON_ERROR);
    }

    private function writer(): User
    {
        return User::factory()->withModules(DashboardModule::Berita)->create();
    }
}
