<?php

namespace Tests\Feature;

use App\Actions\BuildThemeStylesheet;
use App\Enums\ThemeFont;
use App\Models\Setting;
use App\Models\User;
use App\Support\ThemeVariables;
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

    public function test_serves_the_saved_portal_theme_with_its_hosted_font(): void
    {
        $this->saveTheme(['aksen' => '#2F7D4F', 'hurufTeks' => 'inter', 'hurufJudul' => 'lexend', 'sudut' => 'tegas']);

        $css = $this->get('/tema-portal.css')->assertOk()->getContent();

        $this->assertStringStartsWith("/* Tema portal PINTU, diatur Administrator di Pengaturan › Tampilan (D-057). */\n@font-face {\n", $css);
        $this->assertStringContainsString("@font-face {\n  font-family: \"Inter\";\n  src: url(\"/assets/fonts/inter/inter-latin-wght-normal.woff2\") format(\"woff2\");\n  font-weight: 100 900;\n  font-style: normal;\n  font-display: swap;\n}\n", $css);
        $this->assertStringContainsString('src: url("/assets/fonts/lexend/lexend-latin-wght-normal.woff2")', $css);
        $this->assertStringContainsString("\n:root {\n  --accent: #2F7D4F;\n", $css);
        $this->assertStringContainsString('  --font-display: "Lexend", ', $css);
        $this->assertStringEndsWith("  --r-scale: 0.5;\n}\n", $css);
        $this->assertStringNotContainsString('--paper', $css);
        $this->assertStringStartsWith('/* Tema dashboard PINTU: tampilan bawaan', $this->get('/tema-dashboard.css')->getContent());
    }

    public function test_serves_the_saved_dashboard_theme_for_each_mode_above_the_built_stylesheet(): void
    {
        $this->saveTheme(dashboard: ['aksen' => '#2F7D4F', 'latar' => 'netral', 'sudut' => 'membulat']);

        $css = $this->get('/tema-dashboard.css')->assertOk()->getContent();

        $this->assertStringContainsString("\nhtml:root {\n  --night-accent: #88B496;\n  --radius: 0.84375rem;\n}\n", $css);
        $this->assertStringContainsString("\nhtml:root:not(.dark) {\n  --secondary: #F1F1F1;\n", $css);
        $this->assertStringContainsString("  --brand: #2F7D4F;\n", $css);
        $this->assertStringContainsString("\nhtml:root.dark {\n  --brand: #88B496;\n", $css);
        $this->assertStringNotContainsString('@font-face', $css);
        $this->assertStringStartsWith('/* Tema portal PINTU: tampilan bawaan', $this->get('/tema-portal.css')->getContent());
    }

    public function test_scales_every_portal_page_but_the_tv_display_and_the_dashboard_by_its_root_font_size(): void
    {
        $this->saveTheme(['skala' => 120], ['skala' => 90]);

        $this->assertStringEndsWith(
            "\n:root:not(:has([data-react=\"layar-antrean\"])) {\n  zoom: 1.2;\n  --zoom: 1.2;\n}\n",
            $this->get('/tema-portal.css')->assertOk()->getContent(),
        );
        $this->assertStringEndsWith("\nhtml:root {\n  font-size: 90%;\n}\n", $this->get('/tema-dashboard.css')->getContent());
        $this->assertStringContainsString('<div data-react="layar-antrean">', $this->get('/layar')->getContent());
    }

    public function test_covers_the_background_of_the_pages_with_the_header_in_dots_that_scroll_with_them(): void
    {
        $this->saveTheme(['motif' => 'titik']);

        $this->assertSame(
            "/* Tema portal PINTU, diatur Administrator di Pengaturan › Tampilan (D-057). */\n"
            ."body:has(.nav-wrap) {\n"
            ."  background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16'%3E%3Ccircle cx='1' cy='1' r='1' fill='rgba(163,163,163,0.8)'/%3E%3C/svg%3E\");\n"
            ."  background-size: 16px 16px;\n"
            ."}\n",
            $this->get('/tema-portal.css')->assertOk()->getContent(),
        );
        $this->assertStringContainsString('<header class="nav-wrap">', $this->get('/layanan')->getContent());
        $this->assertStringContainsString('<header class="nav-wrap">', $this->get('/halaman-tidak-ada')->getContent());
        $this->assertStringNotContainsString('nav-wrap', $this->get('/login')->getContent());
        $this->assertStringNotContainsString('nav-wrap', $this->get('/layar')->getContent());
        $this->assertStringNotContainsString('nav-wrap', (string) file_get_contents(resource_path('views/auth/aktivasi.blade.php')));
    }

    public function test_draws_the_dots_in_a_darker_grey_on_the_dark_portal(): void
    {
        $this->saveTheme(['motif' => 'titik', 'mode' => 'gelap']);

        $css = $this->get('/tema-portal.css')->assertOk()->getContent();

        $this->assertStringEndsWith("r='1' fill='rgba(82,82,82,0.8)'/%3E%3C/svg%3E\");\n  background-size: 16px 16px;\n}\n", $css);
        $this->assertStringNotContainsString('163,163,163', $css);
    }

    public function test_reads_a_portal_theme_without_a_pattern_such_as_an_earlier_preview_as_plain(): void
    {
        $this->assertSame("/* Tema portal PINTU: tampilan bawaan (D-057). */\n", app(BuildThemeStylesheet::class)->portal(Setting::DEFAULT_THEME_SECTION));
    }

    public function test_enlarges_the_dashboard_only_on_screens_from_64rem_wide(): void
    {
        $this->saveTheme(dashboard: ['skala' => 110]);

        $this->assertStringEndsWith(
            "\n@media (min-width: 64rem) {\n  html:root {\n    font-size: 110%;\n  }\n}\n",
            $this->get('/tema-dashboard.css')->assertOk()->getContent(),
        );
    }

    public function test_leaves_out_the_blocks_of_the_modes_a_dashboard_theme_does_not_change(): void
    {
        $this->saveTheme(dashboard: ['sudut' => 'tegas']);

        $css = $this->get('/tema-dashboard.css')->assertOk()->getContent();

        $this->assertStringEndsWith("\nhtml:root {\n  --radius: 0.3125rem;\n}\n", $css);
        $this->assertSame(1, substr_count($css, '{'));
    }

    public function test_serves_the_portal_dark_with_its_white_footer_logo_and_leaves_the_dashboard_alone(): void
    {
        $this->saveTheme(['mode' => 'gelap']);

        $css = $this->get('/tema-portal.css')->assertOk()->getContent();

        $this->assertStringContainsString("\n:root {\n  color-scheme: dark;\n  --paper: #100F0D;\n", $css);
        $this->assertStringContainsString('  --chrome: #232321;', $css);
        $this->assertStringEndsWith("}\n.footer__logo { content: url(\"/assets/img/logo-pintu-lockup-putih.png\"); }\n", $css);
        $this->assertFileExists(public_path('assets/img/logo-pintu-lockup-putih.png'));
        $this->assertStringNotContainsString('{', $this->get('/tema-dashboard.css')->getContent());
    }

    public function test_sets_the_heading_weight_and_gives_the_dashboard_titles_the_heading_font(): void
    {
        $this->saveTheme(['tebalJudul' => 800], ['hurufJudul' => 'plus-jakarta-sans', 'tebalJudul' => 700]);

        $this->assertStringEndsWith("\n:root {\n  --fw-display: 800;\n}\n", $this->get('/tema-portal.css')->assertOk()->getContent());
        $dashboard = $this->get('/tema-dashboard.css')->assertOk()->getContent();
        $this->assertStringContainsString("\nhtml:root {\n  --font-display: \"Plus Jakarta Sans\", ", $dashboard);
        $this->assertStringContainsString("\n  --font-heading: \"Plus Jakarta Sans\", ", $dashboard);
        $this->assertStringEndsWith("\n  --fw-heading: 700;\n}\n", $dashboard);
    }

    public function test_colours_the_portal_buttons_and_navigation_and_the_dashboard_main_buttons_in_light_mode(): void
    {
        $this->saveTheme(['gelap' => '#0F1B3D'], ['gelap' => '#0F1B3D']);

        $portal = $this->get('/tema-portal.css')->assertOk()->getContent();
        $this->assertStringContainsString("\n:root {\n  --ink-fill: #0F1B3D;\n  --ink-hover: ", $portal);
        $this->assertStringContainsString("\n  --chrome: #0F1B3D;\n  --chrome-2: ", $portal);
        $this->assertStringNotContainsString('--night', $portal);
        $this->assertStringEndsWith("\nhtml:root:not(.dark) {\n  --primary: #0F1B3D;\n}\n", $this->get('/tema-dashboard.css')->getContent());
    }

    public function test_colours_the_portal_dark_panels(): void
    {
        $this->saveTheme(['panel' => '#0C1839']);

        $portal = $this->get('/tema-portal.css')->assertOk()->getContent();
        $this->assertStringContainsString("\n:root {\n  --night: #0C1839;\n  --night-2: ", $portal);
        $this->assertStringContainsString("\n  --night-accent: ", $portal);
        $this->assertStringNotContainsString('--chrome', $portal);
        $this->assertStringNotContainsString('{', $this->get('/tema-dashboard.css')->getContent());
    }

    public function test_serves_a_light_navbar_with_the_dark_logo_and_a_glass_navbar_shaped_like_the_notch(): void
    {
        $this->saveTheme(['navbar' => '#FFFFFF']);

        $light = $this->get('/tema-portal.css')->assertOk()->getContent();
        $this->assertStringContainsString("  --nav-surface: #FFFFFF;\n  --nav-tint: 15, 19, 25;\n", $light);
        $this->assertStringEndsWith("}\n.nav__logo { content: url(\"/assets/img/logo-pintu-nav.png\"); }\n", $light);
        $this->assertFileExists(public_path('assets/img/logo-pintu-nav.png'));
        $this->assertStringNotContainsString('backdrop-filter', $light);

        $this->saveTheme(['kaca' => 'sedang']);

        $glass = $this->get('/tema-portal.css')->assertOk()->getContent();
        $this->assertStringContainsString("  --nav-blur: 18px;\n", $glass);
        $this->assertStringContainsString(".nav { background: transparent; }\n.nav::after { display: none; }\n.nav::before {\n", $glass);
        $this->assertStringContainsString('  backdrop-filter: blur(var(--nav-blur)) saturate(1.4);', $glass);
        $this->assertStringContainsString(".nav__menu, .mobile-menu, .cari-panel {\n", $glass);

        $this->assertSame(1, preg_match('/^\.nav::before \{\n  clip-path: polygon\((.+)\);\n\}$/m', $glass, $outline));
        $points = explode(', ', $outline[1]);
        $this->assertCount(52, $points);
        $this->assertSame([
            '100% 0',
            'calc(100% - var(--nav-lekuk) * 0.7071) calc(var(--nav-lekuk) * 0.2929)',
            'calc(100% - var(--nav-lekuk)) var(--nav-lekuk)',
            'calc(100% - var(--nav-lekuk)) calc(100% - var(--nav-r))',
            'calc(100% - var(--nav-lekuk) - var(--nav-r) * 0.2929) calc(100% - var(--nav-r) * 0.2929)',
            'calc(100% - var(--nav-lekuk) - var(--nav-r)) 100%',
            'calc(var(--nav-lekuk) + var(--nav-r)) 100%',
            'calc(var(--nav-lekuk) + var(--nav-r) * 0.2929) calc(100% - var(--nav-r) * 0.2929)',
            'var(--nav-lekuk) calc(100% - var(--nav-r))',
            'var(--nav-lekuk) var(--nav-lekuk)',
            'calc(var(--nav-lekuk) * 0.7071) calc(var(--nav-lekuk) * 0.2929)',
            '0 0',
        ], array_values(array_intersect_key($points, array_flip([0, 6, 12, 13, 19, 25, 26, 32, 38, 39, 45, 51]))));
        $this->assertStringNotContainsString('logo-pintu-nav.png', $glass);
        $this->assertStringNotContainsString('{', $this->get('/tema-dashboard.css')->getContent());
    }

    public function test_serves_only_a_comment_for_a_saved_theme_equal_to_the_built_in_look(): void
    {
        $this->saveTheme(['aksen' => '#2b63f6']);

        $this->assertStringNotContainsString('{', $this->get('/tema-portal.css')->getContent());
        $this->assertStringNotContainsString('{', $this->get('/tema-dashboard.css')->getContent());
    }

    public function test_serves_a_saved_theme_to_a_browser_holding_the_previous_one(): void
    {
        $etag = $this->get('/tema-portal.css')->headers->get('ETag');
        $this->saveTheme(['latar' => 'sejuk']);

        $response = $this->get('/tema-portal.css', ['If-None-Match' => $etag]);

        $response->assertOk();
        $this->assertStringContainsString('--paper: #F4F6FA;', $response->getContent());
    }

    public function test_keeps_the_built_in_values_equal_to_the_stylesheets_they_override(): void
    {
        $portal = $this->declarations(public_path('assets/css/tokens.css'));
        foreach ([...ThemeVariables::PORTAL_BACKGROUND, ...ThemeVariables::PORTAL_TEXT, ...ThemeVariables::PORTAL_ACCENT] as $name => $value) {
            $this->assertSame($value, $portal[$name], $name);
        }
        $this->assertSame(ThemeVariables::SYSTEM_TEXT_FONT, $portal['--font-sans']);
        $this->assertSame(ThemeVariables::SYSTEM_DISPLAY_FONT, $portal['--font-display']);
        $this->assertSame((string) ThemeVariables::DEFAULT_HEADING_WEIGHT, $portal['--fw-display']);
        $this->assertSame(ThemeVariables::DEFAULT_DARK, $portal['--chrome']);
        $this->assertSame('var(--ink)', $portal['--ink-fill']);
        $this->assertSame('#17181B', $portal['--chrome-2']);
        $this->assertSame(ThemeVariables::PORTAL_PANEL['--night-line'], $portal['--chrome-line']);
        $this->assertSame('var(--chrome)', $portal['--nav-surface']);
        $this->assertSame('255, 255, 255', $portal['--nav-tint']);
        $this->assertSame('#FFFFFF', $portal['--nav-cta']);
        $this->assertSame('var(--chrome)', $portal['--nav-cta-text']);
        foreach (ThemeVariables::PORTAL_PANEL as $name => $value) {
            $this->assertSame($value, $portal[$name], $name);
        }

        $index = (string) file_get_contents(resource_path('js/dashboard/index.css'));
        $light = $this->declarations(substr($index, strpos($index, "\n:root {")));
        $dark = $this->declarations(substr($index, strpos($index, "\n.dark {")));
        foreach ([...ThemeVariables::DASHBOARD_BACKGROUND, ...ThemeVariables::DASHBOARD_LIGHT_ACCENT, '--radius' => ThemeVariables::DASHBOARD_RADIUS, '--fw-heading' => (string) ThemeVariables::DEFAULT_HEADING_WEIGHT, '--primary' => ThemeVariables::DASHBOARD_PRIMARY] as $name => $value) {
            $this->assertSame($value, $light[$name], $name);
        }
        foreach (ThemeVariables::DASHBOARD_DARK_ACCENT as $name => $value) {
            $this->assertSame($value, $dark[$name], $name);
        }
        $fonts = $this->declarations(substr($index, strpos($index, "\n@theme {")));
        $this->assertSame(ThemeVariables::SYSTEM_TEXT_FONT, $fonts['--font-sans']);
        $this->assertSame(ThemeVariables::SYSTEM_DISPLAY_FONT, $fonts['--font-display']);
        $this->assertSame('var(--font-sans)', $fonts['--font-heading']);
    }

    public function test_hosts_every_font_with_its_licence(): void
    {
        foreach (ThemeFont::cases() as $font) {
            if ($font === ThemeFont::Sistem) {
                continue;
            }
            $this->assertStringStartsWith('wOF2', (string) file_get_contents(public_path($font->file())), $font->value);
            $this->assertStringContainsString('SIL OPEN FONT LICENSE Version 1.1', (string) file_get_contents(public_path("assets/fonts/{$font->value}/OFL.txt")), $font->value);
        }
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
     * Save a theme as an administrator: the built-in look unless overridden.
     *
     * @param  array<string, string>  $portal
     * @param  array<string, string>  $dashboard
     */
    private function saveTheme(array $portal = [], array $dashboard = []): void
    {
        $this->actingAs(User::factory()->administrator()->create())->putJson('/api/pengaturan/tema', [
            'portal' => [...Setting::DEFAULT_PORTAL_THEME, ...$portal],
            'dashboard' => [...Setting::DEFAULT_THEME_SECTION, ...$dashboard],
        ])->assertOk();
    }

    /**
     * The custom properties of the first block in a stylesheet (a path) or in
     * stylesheet text, by name.
     *
     * @return array<string, string>
     */
    private function declarations(string $stylesheet): array
    {
        $css = is_file($stylesheet) ? (string) file_get_contents($stylesheet) : $stylesheet;
        $block = (string) preg_replace('#/\*.*?\*/#s', '', substr($css, 0, (int) strpos($css, '}')));
        preg_match_all('/(--[\w-]+):\s*([^;]+);/', $block, $matches);

        return array_combine($matches[1], array_map('trim', $matches[2]));
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
