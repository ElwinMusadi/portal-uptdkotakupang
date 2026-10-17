<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\Setting;
use App\Models\User;
use App\Support\ThemePreview;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ThemePreviewLinkTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_401_to_guests(): void
    {
        $this->postJson('/api/pengaturan/tema/tautan-pratinjau', $this->theme())->assertUnauthorized();
    }

    public function test_makes_links_only_for_administrators(): void
    {
        $employee = User::factory()->withModules(...DashboardModule::cases())->create();

        $this->actingAs($employee)->postJson('/api/pengaturan/tema/tautan-pratinjau', $this->theme())
            ->assertForbidden()
            ->assertExactJson(['message' => 'Hanya Administrator yang dapat mengubah Tampilan.']);
    }

    public function test_rejects_an_invalid_theme(): void
    {
        $this->actingAs(User::factory()->administrator()->create())
            ->postJson('/api/pengaturan/tema/tautan-pratinjau', $this->theme(['aksen' => 'merah']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['portal.aksen' => 'Warna utama portal harus berupa kode warna #RRGGBB.']);
    }

    public function test_shows_the_unsaved_portal_theme_only_to_the_browser_that_opens_the_link(): void
    {
        $this->freezeTime();
        $response = $this->actingAs(User::factory()->administrator()->create())
            ->postJson('/api/pengaturan/tema/tautan-pratinjau', $this->theme(['aksen' => '#2F7D4F', 'skala' => 110]));
        $response->assertCreated()->assertJsonPath('berlaku', now()->addMinutes(ThemePreview::MINUTES)->toIso8601String());
        $this->assertMatchesRegularExpression('#^'.preg_quote(url('/pratinjau-tema/'), '#').'/[A-Za-z0-9]{40}$#', $response->json('tautan'));
        $this->assertDatabaseCount('settings', 0);

        $opened = $this->get($response->json('tautan'));

        $opened->assertRedirect('/');
        $token = $opened->getCookie(ThemePreview::COOKIE)->getValue();
        $this->assertSame($token, basename($response->json('tautan')));
        $this->assertStringNotContainsString('{', $this->get('/tema-portal.css')->getContent());
        $preview = $this->withCookie(ThemePreview::COOKIE, $token)->get('/tema-portal.css')->getContent();
        $this->assertStringContainsString('  --accent: #2F7D4F;', $preview);
        $this->assertStringContainsString(":root:not(:has([data-react=\"layar-antrean\"])) {\n  zoom: 1.1;\n  --zoom: 1.1;\n}\n", $preview);
        $this->assertStringContainsString('content: "Pratinjau tema · belum disimpan";', $preview);
        $this->assertStringEndsWith("}\n", $preview);
        $this->assertStringNotContainsString('{', $this->get('/tema-dashboard.css')->getContent());
    }

    public function test_marks_a_preview_of_the_built_in_look_too(): void
    {
        $token = ThemePreview::store(Setting::DEFAULT_PORTAL_THEME);

        $preview = $this->withCookie(ThemePreview::COOKIE, $token)->get('/tema-portal.css')->getContent();

        $this->assertStringStartsWith('/* Tema portal PINTU: tampilan bawaan', $preview);
        $this->assertStringContainsString('body::after {', $preview);
    }

    public function test_ends_the_preview_when_the_link_expires(): void
    {
        $token = ThemePreview::store([...Setting::DEFAULT_PORTAL_THEME, 'aksen' => '#2F7D4F']);
        $this->travel(ThemePreview::MINUTES + 1)->minutes();

        $this->assertStringNotContainsString('{', $this->withCookie(ThemePreview::COOKIE, $token)->get('/tema-portal.css')->getContent());
        $opened = $this->get("/pratinjau-tema/{$token}");
        $opened->assertRedirect('/');
        $this->assertTrue($opened->getCookie(ThemePreview::COOKIE, false)->isCleared());
    }

    public function test_ends_the_preview_on_request(): void
    {
        $response = $this->get('/pratinjau-tema/selesai');

        $response->assertRedirect('/');
        $this->assertTrue($response->getCookie(ThemePreview::COOKIE, false)->isCleared());
    }

    public function test_ignores_a_cookie_that_is_not_a_token(): void
    {
        $this->assertNull(ThemePreview::find('../../etc/passwd'));
        $this->assertStringNotContainsString('{', $this->withCookie(ThemePreview::COOKIE, 'x')->get('/tema-portal.css')->getContent());
        $this->get('/pratinjau-tema/'.str_repeat('a', 39))->assertNotFound();
    }

    /**
     * A complete theme document: the built-in look unless the portal part is
     * overridden.
     *
     * @param  array<string, string|int>  $portal
     * @return array<string, mixed>
     */
    private function theme(array $portal = []): array
    {
        return ['portal' => [...Setting::DEFAULT_PORTAL_THEME, ...$portal], 'dashboard' => Setting::DEFAULT_THEME_SECTION];
    }
}
