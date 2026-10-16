<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ThemeStylesheetTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[TestWith(['/tema-portal.css', 'Tema portal PINTU'])]
    #[TestWith(['/tema-dashboard.css', 'Tema dashboard PINTU'])]
    public function test_serves_a_theme_stylesheet_without_rules_until_a_theme_is_saved(string $uri, string $comment): void
    {
        $response = $this->get($uri);

        $response->assertOk()->assertHeader('Content-Type', 'text/css; charset=UTF-8');
        $this->assertStringStartsWith("/* {$comment}: tampilan bawaan", $response->getContent());
        $this->assertStringNotContainsString('{', $response->getContent());
        $this->assertSame([], $response->headers->getCookies());
    }

    #[TestWith(['/tema-portal.css'])]
    #[TestWith(['/tema-dashboard.css'])]
    public function test_has_the_browser_revalidate_the_theme_on_every_load(string $uri): void
    {
        $response = $this->get($uri);

        $this->assertTrue($response->headers->hasCacheControlDirective('no-cache'));
        $this->assertNotEmpty($response->headers->get('ETag'));
        $this->get($uri, ['If-None-Match' => $response->headers->get('ETag')])->assertStatus(304)->assertContent('');
    }

    public function test_loads_the_portal_theme_last_on_the_display_and_activation_pages(): void
    {
        $this->assertSame('tema-portal.css', $this->lastStylesheet($this->get('/layar')->assertOk()->getContent()));
        $this->assertSame(route('tema.portal'), $this->lastStylesheet($this->get('/aktivasi/'.User::factory()->create()->id)->assertGone()->getContent()));
    }

    #[TestWith(['/dashboard'])]
    #[TestWith(['/absen'])]
    public function test_loads_the_dashboard_theme_on_the_dashboard_and_the_attendance_app(string $uri): void
    {
        $this->withoutVite();

        $html = $this->actingAs(User::factory()->create())->get($uri)->assertOk()->getContent();

        $this->assertSame(route('tema.dashboard'), $this->lastStylesheet($html));
    }

    /**
     * The address of the page's last stylesheet link.
     */
    private function lastStylesheet(string $html): string
    {
        preg_match_all('/<link rel="stylesheet" href="([^"]+)"/', $html, $links);

        return end($links[1]);
    }
}
