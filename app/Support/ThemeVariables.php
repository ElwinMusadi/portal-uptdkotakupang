<?php

namespace App\Support;

use App\Enums\ThemeBackground;
use App\Enums\ThemeCorners;
use App\Enums\ThemeFont;

/**
 * The CSS variables a theme section sets (D-057). The built-in PINTU look is
 * the hand-tuned values of public/assets/css/tokens.css and the dashboard's
 * index.css, copied below; a section changes only the variables whose
 * setting differs from it, and colours derived from another accent are
 * computed in OKLCH and kept readable (ThemePalette).
 *
 * @phpstan-type ThemeSection array{aksen: string, latar: string, hurufTeks: string, hurufJudul: string, sudut: string, skala: int}
 */
final class ThemeVariables
{
    /**
     * The built-in accent, PINTU blue.
     */
    public const string DEFAULT_ACCENT = '#2B63F6';

    /**
     * The portal's background and line colours (tokens.css), in the warm
     * built-in shade.
     *
     * @var array<string, string>
     */
    public const array PORTAL_BACKGROUND = [
        '--paper' => '#F7F6F2',
        '--paper-2' => '#EFEDE6',
        '--paper-3' => '#E9E6DD',
        '--line' => '#E7E4DC',
        '--line-2' => '#D9D5CA',
        '--line-3' => '#C6C1B4',
        '--surface-2' => '#FBFAF7',
        '--surface-hover' => '#ECEAE4',
        '--map' => '#ECE9E1',
    ];

    /**
     * The portal's accent colours (tokens.css) for the built-in accent.
     *
     * @var array<string, string>
     */
    public const array PORTAL_ACCENT = [
        '--accent' => '#2B63F6',
        '--accent-ink' => '#2457E0',
        '--accent-soft' => '#E8EEFE',
        '--accent-line' => '#C9D6FD',
        '--accent-halo' => 'rgba(43, 99, 246, .18)',
        '--night-accent' => '#7FA4FF',
        '--night-accent-2' => '#B5C8FF',
        '--night-accent-soft' => 'rgba(127, 164, 255, .1)',
        '--night-accent-ring' => 'rgba(127, 164, 255, .35)',
        '--night-accent-dim' => '#23345F',
    ];

    /**
     * The system font stacks of the portal and the dashboard.
     */
    public const string SYSTEM_TEXT_FONT = '"SF Pro Text", -apple-system, BlinkMacSystemFont, system-ui, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';

    public const string SYSTEM_DISPLAY_FONT = '"SF Pro Display", -apple-system, BlinkMacSystemFont, system-ui, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';

    /**
     * The system fonts that follow a hosted font, for characters it lacks.
     */
    private const string FALLBACK_FONTS = '-apple-system, BlinkMacSystemFont, system-ui, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';

    /**
     * The dashboard's light-mode background and line colours (index.css),
     * in the warm built-in shade.
     *
     * @var array<string, string>
     */
    public const array DASHBOARD_BACKGROUND = [
        '--secondary' => '#f2f1ec',
        '--muted' => '#f2f1ec',
        '--accent' => '#f0eee8',
        '--border' => '#e7e4dc',
        '--input' => '#dcd8cd',
        '--sidebar' => '#f7f6f2',
        '--sidebar-accent' => '#ecebe5',
        '--sidebar-border' => '#e7e4dc',
    ];

    /**
     * The dashboard's light-mode accent colours (index.css).
     *
     * @var array<string, string>
     */
    public const array DASHBOARD_LIGHT_ACCENT = [
        '--brand' => '#2b63f6',
        '--ring' => '#2b63f6',
        '--sidebar-ring' => '#2b63f6',
        '--chart-1' => '#2b63f6',
        '--chart-2' => '#9cb8ff',
        '--chart-3' => '#1a3fa8',
        '--chart-4' => '#5f87f8',
    ];

    /**
     * The dashboard's dark-mode accent colours (index.css).
     *
     * @var array<string, string>
     */
    public const array DASHBOARD_DARK_ACCENT = [
        '--brand' => '#7fa4ff',
        '--ring' => '#7fa4ff',
        '--sidebar-ring' => '#7fa4ff',
        '--chart-1' => '#7fa4ff',
        '--chart-2' => '#2b63f6',
        '--chart-3' => '#c3d4ff',
        '--chart-4' => '#4c7cf7',
        '--sidebar-primary' => '#2b63f6',
    ];

    /**
     * The dashboard's corner radius (index.css).
     */
    public const string DASHBOARD_RADIUS = '0.625rem';

    /**
     * The dark surfaces accent colours are read on: the portal's dark panels
     * (--night, --night-2, --night-3) and the dashboard's dark mode
     * (background, card, popover, muted).
     *
     * @var list<string>
     */
    private const array PORTAL_NIGHT = ['#0A0A0C', '#17181B', '#202125'];

    private const array DASHBOARD_DARK_SURFACES = ['#0D0E11', '#141519', '#17181B', '#222328'];

    /**
     * The portal tokens the section changes: only those whose setting differs
     * from the built-in look.
     *
     * @param  ThemeSection  $section
     * @return array<string, string>
     */
    public static function portal(array $section): array
    {
        $background = ThemeBackground::from($section['latar']);
        $backgroundColours = $background->shades(self::PORTAL_BACKGROUND);
        $variables = $background === ThemeBackground::Hangat ? [] : $backgroundColours;

        if (! self::hasDefaultAccent($section)) {
            $variables += self::portalAccent($section['aksen'], $backgroundColours);
        }

        $variables += self::fonts($section);

        $corners = ThemeCorners::from($section['sudut']);
        if ($corners !== ThemeCorners::Sedang) {
            $variables['--r-scale'] = self::number($corners->factor());
        }

        return $variables;
    }

    /**
     * The zoom of the portal pages for the section's scale, with --zoom,
     * which full-height rules divide by (D-057); nothing at 100%.
     *
     * @param  ThemeSection  $section
     * @return array<string, string>
     */
    public static function portalZoom(array $section): array
    {
        if ($section['skala'] === 100) {
            return [];
        }

        $zoom = self::number($section['skala'] / 100);

        return ['zoom' => $zoom, '--zoom' => $zoom];
    }

    /**
     * The dashboard's root font size for the section's scale, which every
     * rem length of the dashboard follows (D-057); null at 100%.
     *
     * @param  ThemeSection  $section
     */
    public static function dashboardFontSize(array $section): ?string
    {
        return $section['skala'] === 100 ? null : "{$section['skala']}%";
    }

    /**
     * Every themable portal token with the section's value, for the
     * dashboard's preview of the portal.
     *
     * @param  ThemeSection  $section
     * @return array<string, string>
     */
    public static function portalPreview(array $section): array
    {
        return array_merge(
            self::PORTAL_BACKGROUND,
            self::PORTAL_ACCENT,
            ['--font-sans' => self::SYSTEM_TEXT_FONT, '--font-display' => self::SYSTEM_DISPLAY_FONT, '--r-scale' => '1'],
            self::portal($section),
        );
    }

    /**
     * The dashboard variables the section changes, by where they apply: both
     * modes, light mode only, and dark mode only.
     *
     * @param  ThemeSection  $section
     * @return array{both: array<string, string>, light: array<string, string>, dark: array<string, string>}
     */
    public static function dashboard(array $section): array
    {
        $background = ThemeBackground::from($section['latar']);
        $backgroundColours = $background->shades(self::DASHBOARD_BACKGROUND);
        $both = self::fonts($section);
        $light = $background === ThemeBackground::Hangat ? [] : $backgroundColours;
        $dark = [];

        if (! self::hasDefaultAccent($section)) {
            [$lightAccent, $dark] = self::dashboardAccent($section['aksen'], $backgroundColours);
            $light += $lightAccent;
            // The Papan Layanan preview shows the portal's dark panel
            $both['--night-accent'] = $dark['--brand'];
        }

        $corners = ThemeCorners::from($section['sudut']);
        if ($corners !== ThemeCorners::Sedang) {
            $both['--radius'] = self::number(0.625 * $corners->factor()).'rem';
        }

        return ['both' => $both, 'light' => $light, 'dark' => $dark];
    }

    /**
     * The hosted fonts the section uses, each once.
     *
     * @param  ThemeSection  $section
     * @return list<ThemeFont>
     */
    public static function fontFaces(array $section): array
    {
        [$text, $display] = self::chosenFonts($section);
        $fonts = $text === $display ? [$text] : [$text, $display];

        return array_values(array_filter($fonts, fn (ThemeFont $font): bool => $font !== ThemeFont::Sistem));
    }

    /**
     * The contrast of the portal's colour pairs that carry text, for the
     * preview.
     *
     * @param  ThemeSection  $section
     * @return list<array{pasangan: string, rasio: float}>
     */
    public static function portalContrasts(array $section): array
    {
        $colours = self::portalPreview($section);

        return self::contrasts([
            'Teks putih pada tombol aksen' => ['#FFFFFF', $colours['--accent']],
            'Aksen pada latar halaman' => [$colours['--accent'], $colours['--paper']],
            'Tautan kecil pada latar halaman' => [$colours['--accent-ink'], $colours['--paper']],
            'Tautan kecil pada kotak abu' => [$colours['--accent-ink'], $colours['--paper-2']],
            'Aksen pada panel gelap' => [$colours['--night-accent'], self::PORTAL_NIGHT[1]],
            'Teks pendukung pada latar halaman' => ['#646A72', $colours['--paper']],
        ]);
    }

    /**
     * The contrast of the dashboard's colour pairs that carry text, for the
     * preview.
     *
     * @param  ThemeSection  $section
     * @return list<array{pasangan: string, rasio: float}>
     */
    public static function dashboardContrasts(array $section): array
    {
        $variables = self::dashboard($section);
        $light = array_merge(self::DASHBOARD_BACKGROUND, self::DASHBOARD_LIGHT_ACCENT, $variables['light']);
        $dark = array_merge(self::DASHBOARD_DARK_ACCENT, $variables['dark']);

        return self::contrasts([
            'Teks putih pada tombol warna merek' => ['#FFFFFF', $light['--brand']],
            'Warna merek pada sidebar' => [$light['--brand'], $light['--sidebar']],
            'Warna merek pada kartu mode gelap' => [$dark['--brand'], self::DASHBOARD_DARK_SURFACES[1]],
        ]);
    }

    /**
     * The portal's accent colours derived from the chosen accent.
     *
     * @param  array<string, string>  $background
     * @return array<string, string>
     */
    private static function portalAccent(string $accent, array $background): array
    {
        $soft = ThemePalette::tone($accent, 0.949, 0.1, 0.03);
        $main = ThemePalette::darkenedFor($accent, ['#FFFFFF', $background['--paper']]);
        [$mainLightness] = ThemePalette::oklch($main);
        $ink = ThemePalette::darkenedFor(
            ThemePalette::tone($main, $mainLightness - 0.043, 0.94, 0.4),
            ['#FFFFFF', $background['--paper'], $background['--paper-2'], $soft],
        );
        $night = self::onDark($accent, self::PORTAL_NIGHT);

        return [
            '--accent' => $main,
            '--accent-ink' => $ink,
            '--accent-soft' => $soft,
            '--accent-line' => ThemePalette::tone($accent, 0.879, 0.25, 0.07),
            '--accent-halo' => ThemePalette::rgba($main, 0.18),
            '--night-accent' => $night,
            '--night-accent-2' => ThemePalette::tone($accent, 0.838, 0.35, 0.1),
            '--night-accent-soft' => ThemePalette::rgba($night, 0.1),
            '--night-accent-ring' => ThemePalette::rgba($night, 0.35),
            '--night-accent-dim' => ThemePalette::tone($accent, 0.334, 0.35, 0.09),
        ];
    }

    /**
     * The dashboard's light and dark accent colours derived from the chosen
     * accent.
     *
     * @param  array<string, string>  $background
     * @return array{array<string, string>, array<string, string>}
     */
    private static function dashboardAccent(string $accent, array $background): array
    {
        $brand = ThemePalette::darkenedFor($accent, ['#FFFFFF', $background['--sidebar']]);
        $darkBrand = self::onDark($accent, self::DASHBOARD_DARK_SURFACES);

        return [
            [
                '--brand' => $brand,
                '--ring' => $brand,
                '--sidebar-ring' => $brand,
                '--chart-1' => $brand,
                '--chart-2' => ThemePalette::tone($accent, 0.789, 0.46, 0.12),
                '--chart-3' => ThemePalette::tone($accent, 0.415, 0.76, 0.18),
                '--chart-4' => ThemePalette::tone($accent, 0.65, 0.76, 0.18),
            ],
            [
                '--brand' => $darkBrand,
                '--ring' => $darkBrand,
                '--sidebar-ring' => $darkBrand,
                '--chart-1' => $darkBrand,
                '--chart-2' => $brand,
                '--chart-3' => ThemePalette::tone($accent, 0.871, 0.28, 0.08),
                '--chart-4' => ThemePalette::tone($accent, 0.618, 0.84, 0.2),
                '--sidebar-primary' => $brand,
            ],
        ];
    }

    /**
     * The accent as read on dark surfaces: at least as light as PINTU's
     * #7FA4FF, lightened further until it is readable on all of them.
     *
     * @param  list<string>  $surfaces
     */
    private static function onDark(string $accent, array $surfaces): string
    {
        [$lightness] = ThemePalette::oklch($accent);

        return ThemePalette::lightenedFor(ThemePalette::tone($accent, max($lightness, 0.73), 0.61, 0.16), $surfaces);
    }

    /**
     * The font variables of the section; the system fonts are the built-in
     * look and set nothing.
     *
     * @param  ThemeSection  $section
     * @return array<string, string>
     */
    private static function fonts(array $section): array
    {
        [$text, $display] = self::chosenFonts($section);
        $variables = [];

        if ($text !== ThemeFont::Sistem) {
            $variables['--font-sans'] = "\"{$text->family()}\", ".self::FALLBACK_FONTS;
        }
        if ($display !== ThemeFont::Sistem) {
            $variables['--font-display'] = "\"{$display->family()}\", ".self::FALLBACK_FONTS;
        }

        return $variables;
    }

    /**
     * The text and heading fonts; "sama" headings use the text font.
     *
     * @param  ThemeSection  $section
     * @return array{ThemeFont, ThemeFont}
     */
    private static function chosenFonts(array $section): array
    {
        $text = ThemeFont::from($section['hurufTeks']);

        return [$text, $section['hurufJudul'] === 'sama' ? $text : ThemeFont::from($section['hurufJudul'])];
    }

    /**
     * @param  ThemeSection  $section
     */
    private static function hasDefaultAccent(array $section): bool
    {
        return strtoupper($section['aksen']) === self::DEFAULT_ACCENT;
    }

    /**
     * @param  array<string, array{string, string}>  $pairs
     * @return list<array{pasangan: string, rasio: float}>
     */
    private static function contrasts(array $pairs): array
    {
        return array_map(
            fn (string $pair, array $colours): array => ['pasangan' => $pair, 'rasio' => round(ThemePalette::contrast(...$colours), 2)],
            array_keys($pairs),
            $pairs,
        );
    }

    private static function number(float $value): string
    {
        return rtrim(rtrim(sprintf('%.5F', $value), '0'), '.');
    }
}
