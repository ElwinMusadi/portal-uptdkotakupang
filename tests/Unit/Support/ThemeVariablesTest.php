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

    #[DataProvider('accents')]
    public function test_keeps_every_chosen_dark_dark_and_readable(string $dark): void
    {
        foreach (['#2B63F6', '#F2C46B', '#0B1533', '#00FF66'] as $accent) {
            foreach (['terang', 'gelap'] as $mode) {
                $section = $this->section(['aksen' => $accent, 'gelap' => $dark, 'mode' => $mode]);

                foreach ([...ThemeVariables::portalContrasts($section), ...ThemeVariables::dashboardContrasts($section)] as $pair) {
                    $this->assertGreaterThanOrEqual(ThemePalette::MIN_CONTRAST, $pair['rasio'], "{$dark} {$accent} {$mode}: {$pair['pasangan']}");
                }
            }
            $section = $this->section(['aksen' => $accent, 'gelap' => $dark]);
            $portal = ThemeVariables::portal($section);
            $this->assertGreaterThanOrEqual(ThemePalette::MIN_CONTRAST, ThemePalette::contrast('#FFFFFF', $portal['--ink-hover']), "{$dark} {$accent}: hover");
        }
        foreach ([ThemeVariables::portalDark($this->section(['gelap' => $dark])), ThemeVariables::dashboardDark($this->section(['gelap' => $dark]))] as $used) {
            $this->assertLessThanOrEqual(0.285, ThemePalette::oklch($used)[0], "{$dark} -> {$used}");
        }
    }

    #[DataProvider('colouredAccents')]
    public function test_keeps_the_hue_of_the_chosen_dark(string $dark): void
    {
        foreach ([ThemeVariables::portalDark($this->section(['gelap' => $dark])), ThemeVariables::dashboardDark($this->section(['gelap' => $dark]))] as $used) {
            $this->assertEqualsWithDelta(ThemePalette::oklch($dark)[2], ThemePalette::oklch($used)[2], 6, "{$dark} -> {$used}");
        }
    }

    public function test_colours_the_buttons_and_the_navigation_but_not_the_dark_panels_or_the_text(): void
    {
        $section = $this->section(['gelap' => '#0F1B3D']);

        $portal = ThemeVariables::portal($section);
        $this->assertSame(['--ink-fill', '--ink-hover', '--chrome', '--chrome-2', '--chrome-line'], array_keys($portal));
        $this->assertSame('#0F1B3D', $portal['--ink-fill']);
        $this->assertSame('#0F1B3D', $portal['--chrome']);
        $this->assertGreaterThan(ThemePalette::oklch('#0F1B3D')[0], ThemePalette::oklch($portal['--ink-hover'])[0]);
        $this->assertGreaterThan(ThemePalette::oklch('#0F1B3D')[0], ThemePalette::oklch($portal['--chrome-2'])[0]);
        $this->assertGreaterThan(ThemePalette::oklch($portal['--chrome-2'])[0], ThemePalette::oklch($portal['--chrome-line'])[0]);
        $this->assertSame(['both' => [], 'light' => ['--primary' => '#0F1B3D'], 'dark' => []], ThemeVariables::dashboard($section));
        $this->assertSame('#0F1B3D', ThemeVariables::portalPreview($section)['--ink-fill']);
        $this->assertSame([], ThemeVariables::portal($this->section(['gelap' => '#0f1319'])));

        $dark = ThemeVariables::portal($this->section(['gelap' => '#0F1B3D', 'mode' => 'gelap']));
        $this->assertArrayNotHasKey('--ink-fill', $dark);
        $this->assertSame('#EFEEEB', $dark['--ink']);
        $this->assertGreaterThanOrEqual(ThemePalette::oklch(ThemeVariables::PORTAL_DARK['--chrome'])[0] - 0.0005, ThemePalette::oklch($dark['--chrome'])[0]);
        $this->assertEqualsWithDelta(ThemePalette::oklch('#0F1B3D')[2], ThemePalette::oklch($dark['--chrome'])[2], 6);
        $this->assertGreaterThan(ThemePalette::oklch($dark['--chrome'])[0], ThemePalette::oklch($dark['--chrome-2'])[0]);
        $this->assertGreaterThan(ThemePalette::oklch($dark['--chrome-2'])[0], ThemePalette::oklch($dark['--chrome-line'])[0]);
        $this->assertSame('#EFEEEB', ThemeVariables::portalPreview($this->section(['gelap' => '#0F1B3D', 'mode' => 'gelap']))['--ink-fill']);

        $pairs = array_column(ThemeVariables::portalContrasts($section), 'rasio', 'pasangan');
        $this->assertSame(round(ThemePalette::contrast('#FFFFFF', '#0F1B3D'), 2), $pairs['Teks putih pada tombol gelap']);
        $this->assertSame(round(ThemePalette::contrast('#9BA0A8', '#0F1B3D'), 2), $pairs['Teks abu pada navigasi gelap']);
        $this->assertSame(round(ThemePalette::contrast('#7FA4FF', '#0F1B3D'), 2), $pairs['Aksen pada navigasi gelap']);
        $darkPairs = array_column(ThemeVariables::portalContrasts($this->section(['gelap' => '#0F1B3D', 'mode' => 'gelap'])), 'rasio', 'pasangan');
        $this->assertSame(round(ThemePalette::contrast('#9BA0A8', $dark['--chrome']), 2), $darkPairs['Teks abu pada navigasi gelap']);
        $this->assertSame(round(ThemePalette::contrast('#7FA4FF', $dark['--chrome']), 2), $darkPairs['Aksen pada navigasi gelap']);
        $this->assertSame(round(ThemePalette::contrast('#FFFFFF', '#0F1B3D'), 2), array_column(ThemeVariables::dashboardContrasts($section), 'rasio', 'pasangan')['Teks putih pada tombol utama']);
    }

    #[DataProvider('accents')]
    public function test_keeps_every_panel_colour_dark_and_readable(string $panel): void
    {
        foreach (['#2B63F6', '#F2C46B', '#0B1533', '#00FF66'] as $accent) {
            foreach (['terang', 'gelap'] as $mode) {
                $section = $this->section(['aksen' => $accent, 'panel' => $panel, 'mode' => $mode]);

                foreach (ThemeVariables::portalContrasts($section) as $pair) {
                    $this->assertGreaterThanOrEqual(ThemePalette::MIN_CONTRAST, $pair['rasio'], "{$panel} {$accent} {$mode}: {$pair['pasangan']}");
                }
                $colours = ThemeVariables::portalPreview($section);
                foreach (['--night', '--night-2', '--night-3', '--night-hover'] as $surface) {
                    $this->assertGreaterThanOrEqual(ThemePalette::MIN_CONTRAST, ThemePalette::contrast('#FFFFFF', $colours[$surface]), "{$panel}: putih pada {$surface}");
                    $this->assertGreaterThanOrEqual(ThemePalette::MIN_CONTRAST, ThemePalette::contrast($colours['--night-accent'], $colours[$surface === '--night-hover' ? '--night-3' : $surface]), "{$panel} {$accent}: aksen pada {$surface}");
                }
            }
        }
        $this->assertLessThanOrEqual(0.225, ThemePalette::oklch(ThemeVariables::portalPanel($this->section(['panel' => $panel]))['--night'])[0], $panel);
    }

    public function test_colours_the_dark_panels_in_both_modes_but_not_the_navigation(): void
    {
        $section = $this->section(['panel' => '#0C1839']);

        $portal = ThemeVariables::portal($section);
        $this->assertSame([...array_keys(ThemeVariables::PORTAL_PANEL), '--night-accent', '--night-accent-soft', '--night-accent-ring'], array_keys($portal));
        $this->assertSame('#0C1839', $portal['--night']);
        foreach (['--night-2', '--night-3', '--night-line'] as $name) {
            $this->assertGreaterThan(ThemePalette::oklch('#0C1839')[0], ThemePalette::oklch($portal[$name])[0], $name);
            $this->assertEqualsWithDelta(ThemePalette::oklch('#0C1839')[2], ThemePalette::oklch($portal[$name])[2], 8, $name);
        }
        $this->assertSame(ThemePalette::rgba($portal['--night-accent'], 0.1), $portal['--night-accent-soft']);
        $this->assertSame(ThemePalette::rgba($portal['--night-accent'], 0.35), $portal['--night-accent-ring']);
        $this->assertArrayNotHasKey('--chrome', $portal);
        $this->assertSame(ThemeVariables::PORTAL_PANEL, ThemeVariables::portalPanel($this->section(['panel' => '#0a0a0c'])));
        $this->assertSame([], ThemeVariables::portal($this->section(['panel' => '#0a0a0c'])));

        $dark = ThemeVariables::portal($this->section(['panel' => '#0C1839', 'mode' => 'gelap']));
        $this->assertSame($portal['--night-3'], $dark['--night-3']);
        $this->assertSame($portal['--night-accent'], $dark['--night-accent']);

        $pairs = array_column(ThemeVariables::portalContrasts($section), 'rasio', 'pasangan');
        $this->assertSame(round(ThemePalette::contrast($portal['--night-accent'], $portal['--night-2']), 2), $pairs['Aksen pada panel gelap']);
        $this->assertSame(round(ThemePalette::contrast('#9BA0A8', $portal['--night-3']), 2), $pairs['Teks abu pada panel gelap']);
        $this->assertSame('#0C1839', ThemeVariables::portalPreview($section)['--night']);
        $this->assertSame('#0A0A0C', ThemeVariables::portalPreview($this->section())['--night']);
        $darkPairs = array_column(ThemeVariables::portalContrasts($this->section(['panel' => '#0C1839', 'mode' => 'gelap'])), 'rasio', 'pasangan');
        $this->assertSame(round(ThemePalette::contrast('#9BA0A8', $portal['--night-3']), 2), $darkPairs['Teks abu pada panel gelap']);

        // A deep colour of its own is kept as chosen, beyond the chroma of the lighter boxes
        $this->assertSame('#150050', ThemeVariables::portalPanel($this->section(['panel' => '#150050']))['--night']);

        $green = ThemeVariables::portal($this->section(['aksen' => '#2F7D4F', 'panel' => '#0C1839']));
        $this->assertEqualsWithDelta(ThemePalette::oklch('#2F7D4F')[2], ThemePalette::oklch($green['--night-accent'])[2], 8);
    }

    public function test_keeps_the_navigation_readable_with_the_accent_of_other_panels(): void
    {
        $section = $this->section(['panel' => '#2B63F6', 'gelap' => '#2B63F6']);

        $portal = ThemeVariables::portal($section);
        $this->assertGreaterThanOrEqual(ThemePalette::MIN_CONTRAST, ThemePalette::contrast($portal['--night-accent'], $portal['--chrome']));
        $this->assertSame($portal['--chrome'], ThemeVariables::portalDark($section));
    }

    public function test_deepens_a_light_dark_to_a_dark_shade(): void
    {
        $this->assertSame('#082163', ThemeVariables::portalDark($this->section(['gelap' => '#2B63F6'])));
        $this->assertSame('#0F1B3D', ThemeVariables::portalDark($this->section(['gelap' => '#0F1B3D'])));
        $this->assertSame(ThemeVariables::DEFAULT_DARK, ThemeVariables::dashboardDark($this->section()));
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
        $this->assertFalse(ThemeVariables::isDark(['aksen' => '#2B63F6', 'latar' => 'hangat', 'hurufTeks' => 'sistem', 'hurufJudul' => 'sama', 'sudut' => 'sedang', 'skala' => 100, 'tebalJudul' => 600, 'gelap' => '#0F1319']));
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
            [...array_keys(ThemeVariables::PORTAL_BACKGROUND), ...array_keys(ThemeVariables::PORTAL_TEXT), ...array_keys(ThemeVariables::PORTAL_ACCENT), ...array_keys(ThemeVariables::PORTAL_PANEL), '--font-sans', '--font-display', '--fw-display', '--r-scale', '--ink-fill', '--chrome-2'],
            array_keys($preview),
        );
        $this->assertSame(ThemeBackground::Sejuk->shade('#F7F6F2'), $preview['--paper']);
        $this->assertSame('#2B63F6', $preview['--accent']);
        $this->assertSame(ThemeVariables::SYSTEM_TEXT_FONT, $preview['--font-sans']);
        $this->assertSame('600', $preview['--fw-display']);
        $this->assertSame('#0F1319', $preview['--ink-fill']);
        $this->assertSame('#17181B', $preview['--chrome-2']);
    }

    /**
     * A theme section, the built-in look unless overridden.
     *
     * @param  array<string, string|int>  $overrides
     * @return array{aksen: string, latar: string, hurufTeks: string, hurufJudul: string, sudut: string, skala: int, tebalJudul: int, gelap: string}
     */
    private function section(array $overrides = []): array
    {
        return [...['aksen' => '#2B63F6', 'latar' => 'hangat', 'hurufTeks' => 'sistem', 'hurufJudul' => 'sama', 'sudut' => 'sedang', 'skala' => 100, 'tebalJudul' => 600, 'gelap' => '#0F1319'], ...$overrides];
    }
}
