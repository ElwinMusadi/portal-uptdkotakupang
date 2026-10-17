<?php

namespace Tests\Unit\Support;

use App\Enums\ThemeBackground;
use App\Support\ThemePalette;
use App\Support\ThemeVariables;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class ThemeVariablesTest extends TestCase
{
    /**
     * Accents of every kind: near PINTU blue, the preset colours, a light
     * yellow, a dark navy, a grey, a saturated green and white.
     *
     * @return array<string, array{string}>
     */
    public static function accents(): array
    {
        return [
            'almost pintu blue' => ['#2B63F7'],
            'teal' => ['#0E7490'],
            'green' => ['#2F7D4F'],
            'brick' => ['#C2410C'],
            'maroon' => ['#9F1239'],
            'graphite' => ['#3F4652'],
            'light yellow' => ['#F2C46B'],
            'dark navy' => ['#0B1533'],
            'bright green' => ['#00FF66'],
            'white' => ['#FFFFFF'],
        ];
    }

    /**
     * The accents with a hue to keep: all but the grey and white.
     *
     * @return array<string, array{string}>
     */
    public static function colouredAccents(): array
    {
        return array_diff_key(self::accents(), ['graphite' => true, 'white' => true]);
    }

    public function test_changes_nothing_for_the_built_in_look(): void
    {
        $section = $this->section();

        $this->assertSame([], ThemeVariables::portal($section));
        $this->assertSame(['both' => [], 'light' => [], 'dark' => []], ThemeVariables::dashboard($section));
        $this->assertSame([], ThemeVariables::fontFaces($section));
        $this->assertSame([], ThemeVariables::portalZoom($section));
        $this->assertNull(ThemeVariables::dashboardFontSize($section));
    }

    #[TestWith([90, '0.9'])]
    #[TestWith([110, '1.1'])]
    #[TestWith([120, '1.2'])]
    public function test_scales_the_portal_by_zoom_and_the_dashboard_by_its_root_font_size(int $scale, string $zoom): void
    {
        $section = $this->section(['skala' => $scale]);

        $this->assertSame(['zoom' => $zoom, '--zoom' => $zoom], ThemeVariables::portalZoom($section));
        $this->assertSame([], ThemeVariables::portal($section));
        $this->assertSame(['both' => [], 'light' => [], 'dark' => []], ThemeVariables::dashboard($section));
        $this->assertSame("{$scale}%", ThemeVariables::dashboardFontSize($section));
    }

    public function test_keeps_the_hand_tuned_colours_for_pintu_blue_written_in_lower_case(): void
    {
        $this->assertSame([], ThemeVariables::portal($this->section(['aksen' => '#2b63f6'])));
    }

    #[DataProvider('accents')]
    public function test_keeps_every_text_colour_pair_readable_on_every_background(string $accent): void
    {
        foreach (ThemeBackground::cases() as $background) {
            foreach (['terang', 'gelap'] as $mode) {
                $section = $this->section(['aksen' => $accent, 'latar' => $background->value, 'mode' => $mode]);

                foreach ([...ThemeVariables::portalContrasts($section), ...ThemeVariables::dashboardContrasts($section)] as $pair) {
                    $this->assertGreaterThanOrEqual(ThemePalette::MIN_CONTRAST, $pair['rasio'], "{$accent} {$background->value} {$mode}: {$pair['pasangan']}");
                }
            }
        }
    }

    public function test_turns_the_portal_dark_for_every_visitor_and_keeps_the_dark_panels(): void
    {
        $portal = ThemeVariables::portal($this->section(['mode' => 'gelap']));

        $this->assertSame('dark', $portal['color-scheme']);
        $this->assertSame([], array_diff_key(ThemeVariables::PORTAL_DARK, $portal));
        $this->assertSame('#100F0D', $portal['--paper']);
        $this->assertSame('#EFEEEB', $portal['--ink']);
        $this->assertSame('#100F0D', $portal['--on-ink']);
        $this->assertSame('#5FD39B', $portal['--ok']);
        $this->assertSame('#7FA4FF', $portal['--night-accent']);
        $this->assertGreaterThan(ThemePalette::oklch('#2B63F6')[0], ThemePalette::oklch($portal['--accent'])[0]);
        $this->assertSame(ThemePalette::rgba($portal['--accent'], 0.25), $portal['--accent-halo']);
        $this->assertArrayNotHasKey('--night', $portal);
        $this->assertFalse(ThemeVariables::isDark($this->section()));
        $this->assertFalse(ThemeVariables::isDark(['aksen' => '#2B63F6', 'latar' => 'hangat', 'hurufTeks' => 'sistem', 'hurufJudul' => 'sama', 'sudut' => 'sedang', 'skala' => 100, 'tebalJudul' => 600]));
    }

    #[TestWith(['netral'])]
    #[TestWith(['sejuk'])]
    public function test_shades_the_dark_mode_like_the_light_one(string $background): void
    {
        $portal = ThemeVariables::portal($this->section(['mode' => 'gelap', 'latar' => $background]));

        foreach (ThemeVariables::PORTAL_DARK as $name => $warm) {
            $this->assertEqualsWithDelta(ThemePalette::oklch($warm)[0], ThemePalette::oklch($portal[$name])[0], 0.005, $name);
        }
        $this->assertSame(ThemeBackground::from($background)->shade('#100F0D'), $portal['--paper']);
    }

    public function test_previews_the_dark_portal_with_custom_properties_only(): void
    {
        $preview = ThemeVariables::portalPreview($this->section(['mode' => 'gelap']));

        $this->assertSame('#EFEEEB', $preview['--ink']);
        $this->assertSame('#1A1917', $preview['--surface']);
        $this->assertArrayNotHasKey('color-scheme', $preview);
    }

    #[DataProvider('colouredAccents')]
    public function test_keeps_the_accent_hue_in_every_derived_colour(string $accent): void
    {
        $hue = ThemePalette::oklch($accent)[2];
        $portal = ThemeVariables::portal($this->section(['aksen' => $accent]));
        foreach (['--accent', '--accent-ink', '--accent-line', '--night-accent', '--night-accent-2', '--night-accent-dim'] as $name) {
            $this->assertEqualsWithDelta($hue, ThemePalette::oklch($portal[$name])[2], 6, $name);
        }
    }

    public function test_derives_from_an_accent_next_to_pintu_blue_the_colours_tuned_for_it(): void
    {
        $derived = ThemeVariables::portal($this->section(['aksen' => '#2B63F7']));

        foreach (ThemeVariables::PORTAL_ACCENT as $name => $tuned) {
            if (str_starts_with($tuned, '#')) {
                $this->assertEqualsWithDelta(ThemePalette::oklch($tuned)[0], ThemePalette::oklch($derived[$name])[0], 0.015, $name);
            }
        }
    }

    public function test_writes_the_transparent_accents_with_the_derived_colours(): void
    {
        $portal = ThemeVariables::portal($this->section(['aksen' => '#2F7D4F']));

        $this->assertSame(ThemePalette::rgba($portal['--accent'], 0.18), $portal['--accent-halo']);
        $this->assertSame(ThemePalette::rgba($portal['--night-accent'], 0.1), $portal['--night-accent-soft']);
        $this->assertSame(ThemePalette::rgba($portal['--night-accent'], 0.35), $portal['--night-accent-ring']);
    }

    public function test_sets_the_dashboard_accent_per_mode_and_the_board_preview_in_both(): void
    {
        $dashboard = ThemeVariables::dashboard($this->section(['aksen' => '#2F7D4F']));

        $this->assertSame(['--brand', '--ring', '--sidebar-ring', '--chart-1', '--chart-2', '--chart-3', '--chart-4'], array_keys($dashboard['light']));
        $this->assertSame([...array_keys($dashboard['light']), '--sidebar-primary'], array_keys($dashboard['dark']));
        $this->assertSame($dashboard['light']['--brand'], $dashboard['dark']['--sidebar-primary']);
        $this->assertSame($dashboard['dark']['--brand'], $dashboard['both']['--night-accent']);
    }

    #[TestWith(['netral'])]
    #[TestWith(['sejuk'])]
    public function test_shades_the_background_and_line_colours_with_the_same_lightness(string $background): void
    {
        $portal = ThemeVariables::portal($this->section(['latar' => $background]));
        $dashboard = ThemeVariables::dashboard($this->section(['latar' => $background]));

        $this->assertSame(array_keys(ThemeVariables::PORTAL_BACKGROUND), array_keys($portal));
        $this->assertSame(array_keys(ThemeVariables::DASHBOARD_BACKGROUND), array_keys($dashboard['light']));
        $this->assertSame([], $dashboard['dark']);
        foreach ([...ThemeVariables::PORTAL_BACKGROUND, ...ThemeVariables::DASHBOARD_BACKGROUND] as $name => $warm) {
            $shaded = $portal[$name] ?? $dashboard['light'][$name];
            $this->assertEqualsWithDelta(ThemePalette::oklch($warm)[0], ThemePalette::oklch($shaded)[0], 0.005, $name);
        }
        if ($background === 'netral') {
            $this->assertEqualsWithDelta(0.0, ThemePalette::oklch($portal['--paper'])[1], 0.001);
        } else {
            $this->assertEqualsWithDelta(255, ThemePalette::oklch($portal['--line-3'])[2], 2);
        }
    }

    public function test_sets_the_text_font_for_both_and_a_separate_heading_font_for_headings(): void
    {
        $same = $this->section(['hurufTeks' => 'inter']);
        $this->assertSame('"Inter", -apple-system, BlinkMacSystemFont, system-ui, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif', ThemeVariables::portal($same)['--font-sans']);
        $this->assertSame(ThemeVariables::portal($same)['--font-sans'], ThemeVariables::portal($same)['--font-display']);
        $this->assertSame(['inter'], array_column(ThemeVariables::fontFaces($same), 'value'));

        $separate = $this->section(['hurufTeks' => 'inter', 'hurufJudul' => 'lexend']);
        $this->assertStringStartsWith('"Lexend", ', ThemeVariables::portal($separate)['--font-display']);
        $this->assertSame(['inter', 'lexend'], array_column(ThemeVariables::fontFaces($separate), 'value'));

        $headingsOnly = $this->section(['hurufJudul' => 'manrope']);
        $this->assertSame(['--font-display'], array_keys(ThemeVariables::portal($headingsOnly)));
        $dashboard = ThemeVariables::dashboard($headingsOnly)['both'];
        $this->assertSame(['--font-display', '--font-heading'], array_keys($dashboard));
        $this->assertSame($dashboard['--font-display'], $dashboard['--font-heading']);

        $systemHeadings = $this->section(['hurufTeks' => 'inter', 'hurufJudul' => 'sistem']);
        $this->assertSame(['--font-sans'], array_keys(ThemeVariables::portal($systemHeadings)));
        $this->assertSame(ThemeVariables::SYSTEM_DISPLAY_FONT, ThemeVariables::dashboard($systemHeadings)['both']['--font-heading']);
        $this->assertArrayNotHasKey('--font-heading', ThemeVariables::dashboard($same)['both']);
        $this->assertSame(['inter'], array_column(ThemeVariables::fontFaces($systemHeadings), 'value'));
    }

    #[TestWith([700])]
    #[TestWith([800])]
    public function test_sets_the_heading_weight_of_the_portal_and_the_dashboard(int $weight): void
    {
        $section = $this->section(['tebalJudul' => $weight]);

        $this->assertSame(['--fw-display' => (string) $weight], ThemeVariables::portal($section));
        $this->assertSame(['--fw-heading' => (string) $weight], ThemeVariables::dashboard($section)['both']);
        $this->assertSame((string) $weight, ThemeVariables::portalPreview($section)['--fw-display']);
    }

    #[TestWith(['tegas', '0.5', '0.3125rem'])]
    #[TestWith(['membulat', '1.35', '0.84375rem'])]
    public function test_scales_the_corners(string $corners, string $portalScale, string $dashboardRadius): void
    {
        $section = $this->section(['sudut' => $corners]);

        $this->assertSame(['--r-scale' => $portalScale], ThemeVariables::portal($section));
        $this->assertSame(['--radius' => $dashboardRadius], ThemeVariables::dashboard($section)['both']);
    }

    public function test_previews_every_themable_portal_token(): void
    {
        $preview = ThemeVariables::portalPreview($this->section(['latar' => 'sejuk']));

        $this->assertSame(
            [...array_keys(ThemeVariables::PORTAL_BACKGROUND), ...array_keys(ThemeVariables::PORTAL_TEXT), ...array_keys(ThemeVariables::PORTAL_ACCENT), '--font-sans', '--font-display', '--fw-display', '--r-scale'],
            array_keys($preview),
        );
        $this->assertSame(ThemeBackground::Sejuk->shade('#F7F6F2'), $preview['--paper']);
        $this->assertSame('#2B63F6', $preview['--accent']);
        $this->assertSame(ThemeVariables::SYSTEM_TEXT_FONT, $preview['--font-sans']);
        $this->assertSame('600', $preview['--fw-display']);
    }

    /**
     * A theme section, the built-in look unless overridden.
     *
     * @param  array<string, string|int>  $overrides
     * @return array{aksen: string, latar: string, hurufTeks: string, hurufJudul: string, sudut: string, skala: int, tebalJudul: int}
     */
    private function section(array $overrides = []): array
    {
        return [...['aksen' => '#2B63F6', 'latar' => 'hangat', 'hurufTeks' => 'sistem', 'hurufJudul' => 'sama', 'sudut' => 'sedang', 'skala' => 100, 'tebalJudul' => 600], ...$overrides];
    }
}
