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
 * @phpstan-type ThemeSection array{aksen: string, latar: string, hurufTeks: string, hurufJudul: string, sudut: string, skala: int, tebalJudul: int, gelap: string, panel?: string, mode?: string}
 */
final class ThemeVariables
{
    /**
     * The built-in accent, PINTU blue.
     */
    public const string DEFAULT_ACCENT = '#2B63F6';

    /**
     * The built-in dark of the buttons and the navigation (Irisan 6): the
     * portal's --ink as a fill and --chrome, the dashboard's --primary.
     */
    public const string DEFAULT_DARK = '#0F1319';

    /**
     * How light a chosen dark may be (OKLCH lightness), so it stays a dark;
     * the built-in one is 0.19.
     */
    private const float DARK_MAX_LIGHTNESS = 0.28;

    /**
     * The grey text of the portal's dark surfaces (--night-muted), which
     * also sits on the navigation and the dark cards.
     */
    private const string NIGHT_MUTED = '#9BA0A8';

    /**
     * The built-in colour of the portal's dark panels (Irisan 7), --night.
     */
    public const string DEFAULT_PANEL = '#0A0A0C';

    /**
     * The portal's dark panels (tokens.css): the panel, its boxes, the boxes
     * on hover and the inputs, its lines, and dark buttons on hover.
     *
     * @var array<string, string>
     */
    public const array PORTAL_PANEL = [
        '--night' => '#0A0A0C',
        '--night-2' => '#17181B',
        '--night-3' => '#202125',
        '--night-line' => '#2A2B30',
        '--night-hover' => '#2A2B31',
    ];

    /**
     * How much lighter than --night each panel colour is (OKLCH lightness),
     * as in the built-in panels.
     *
     * @var array<string, float>
     */
    private const array PANEL_STEPS = [
        '--night' => 0,
        '--night-2' => 0.0635,
        '--night-3' => 0.1027,
        '--night-line' => 0.1444,
        '--night-hover' => 0.1444,
    ];

    /**
     * How light a chosen panel colour may be (OKLCH lightness), so the
     * boxes on it stay dark too; the built-in one is 0.15.
     */
    private const float PANEL_MAX_LIGHTNESS = 0.22;

    /**
     * The levels of the portal navbar's glass (Irisan 8): how much of the
     * navbar's colour covers the page behind it, and how far that page is
     * blurred. The cover rises when the links would not read over a white
     * or a black page.
     *
     * @var array<string, array{float, int}>
     */
    private const array NAVBAR_GLASS = ['halus' => [0.9, 12], 'sedang' => [0.8, 18], 'kuat' => [0.68, 26]];

    /**
     * How strong the navbar's links are: its text at 72% (components.css).
     */
    private const float NAVBAR_LINK = 0.72;

    /**
     * The built-in weight of the headings: the portal's --fw-display
     * (tokens.css) and the dashboard's --fw-heading (index.css).
     */
    public const int DEFAULT_HEADING_WEIGHT = 600;

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
     * The portal's text colours and solids (tokens.css) that only the dark
     * mode changes.
     *
     * @var array<string, string>
     */
    public const array PORTAL_TEXT = [
        '--surface' => '#FFFFFF',
        '--ink' => '#0F1319',
        '--ink-2' => '#2A2F37',
        '--ink-hover' => '#262B33',
        '--gray' => '#82878E',
        '--text-2' => '#646A72',
        '--on-ink' => '#FFFFFF',
        '--chrome' => '#0F1319',
    ];

    /**
     * The portal's dark mode (D-057), in the warm shade: backgrounds, lines
     * and text inverted, solids that invert (--ink with --on-ink), and the
     * dark surfaces that stay dark (--chrome), slightly lighter than the
     * page. The dark panels (--night*) keep their colours.
     *
     * @var array<string, string>
     */
    public const array PORTAL_DARK = [
        '--paper' => '#100F0D',
        '--paper-2' => '#181715',
        '--paper-3' => '#201F1D',
        '--line' => '#2C2B29',
        '--line-2' => '#393834',
        '--line-3' => '#51504B',
        '--surface-2' => '#1F1E1C',
        '--surface-hover' => '#292825',
        '--map' => '#201F1D',
        '--surface' => '#1A1917',
        '--ink' => '#EFEEEB',
        '--ink-2' => '#D5D4D1',
        '--ink-hover' => '#CFCECB',
        '--gray' => '#93928E',
        '--text-2' => '#AFAEAA',
        '--on-ink' => '#100F0D',
        '--chrome' => '#232321',
    ];

    /**
     * The status colours and shadows of the portal's dark mode: the light
     * status tones of the dark panels on a faint tint of the same colour.
     *
     * @var array<string, string>
     */
    public const array PORTAL_DARK_STATUS = [
        '--ok' => '#5FD39B',
        '--ok-soft' => 'rgba(95, 211, 155, .14)',
        '--warn' => '#F2C46B',
        '--warn-soft' => 'rgba(242, 196, 107, .14)',
        '--danger' => '#FF8F7E',
        '--danger-soft' => 'rgba(255, 143, 126, .14)',
        '--shadow-soft' => '0 1px 2px rgba(0, 0, 0, .3), 0 12px 32px -16px rgba(0, 0, 0, .6)',
        '--shadow-nav' => '0 10px 30px -12px rgba(0, 0, 0, .7)',
        '--shadow-pop' => '0 24px 60px -20px rgba(0, 0, 0, .7)',
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
     * The dashboard's main buttons in light mode (index.css --primary).
     */
    public const string DASHBOARD_PRIMARY = '#0f1319';

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

        $accent = self::hasDefaultAccent($section) ? self::PORTAL_ACCENT : self::portalAccent($section['aksen'], $backgroundColours);
        // Panels of another colour, in both modes, with the accent made readable on them
        $panel = self::hasDefaultPanel($section) ? [] : [...self::portalPanel($section), ...self::panelAccent($section)];
        $accent = [...$accent, ...$panel];

        if (self::isDark($section)) {
            $dark = $background->shades(self::PORTAL_DARK);
            $variables = ['color-scheme' => 'dark', ...$dark, ...self::PORTAL_DARK_STATUS, ...$accent, ...self::portalDarkAccent($section['aksen'], $dark)];

            if (! self::hasDefaultDark($section)) {
                // The fills stay inverted; only the surfaces that stay dark take the colour
                [$lightness] = ThemePalette::oklch($section['gelap']);
                [$chromeLightness] = ThemePalette::oklch($dark['--chrome']);
                $chrome = ThemePalette::darkenedFor(
                    ThemePalette::tone($section['gelap'], max(min($lightness, self::DARK_MAX_LIGHTNESS), $chromeLightness), 1, 0.12),
                    ['#FFFFFF', self::NIGHT_MUTED, $accent['--night-accent']],
                );
                $variables = [...$variables, '--chrome' => $chrome, '--chrome-2' => self::lighter($chrome, 0.05), '--chrome-line' => self::lighter($chrome, 0.105)];
            }
            // Light buttons stay light on hover: the dark mode's --surface-hover is dark
            $variables['--light-hover'] = $backgroundColours['--surface-hover'];
        } else {
            $variables = $background === ThemeBackground::Hangat ? [] : $backgroundColours;

            if (! self::hasDefaultAccent($section)) {
                $variables += $accent;
            }
            $variables += $panel;

            if (! self::hasDefaultDark($section)) {
                $fill = self::portalDark($section);
                $variables += [
                    '--ink-fill' => $fill,
                    '--ink-hover' => ThemePalette::darkenedFor(self::lighter($fill, 0.08), ['#FFFFFF']),
                    '--chrome' => $fill,
                    '--chrome-2' => self::lighter($fill, 0.05),
                    '--chrome-line' => self::lighter($fill, 0.105),
                ];
            }
        }

        $chrome = $variables['--chrome'] ?? self::DEFAULT_DARK;
        $variables += self::portalNavbar($section, $chrome, self::isDark($section) ? $chrome : ($variables['--ink-fill'] ?? self::DEFAULT_DARK));

        $variables += self::fonts($section);

        if ($section['tebalJudul'] !== self::DEFAULT_HEADING_WEIGHT) {
            $variables['--fw-display'] = (string) $section['tebalJudul'];
        }

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
     * The portal's dark buttons and navigation in light mode: the section's
     * dark, kept dark and readable under white text, the grey text of the
     * dark surfaces, and the accent that marks the current page.
     *
     * @param  ThemeSection  $section
     */
    public static function portalDark(array $section): string
    {
        if (self::hasDefaultDark($section)) {
            return self::DEFAULT_DARK;
        }

        $nightAccent = match (true) {
            ! self::hasDefaultPanel($section) => self::panelAccent($section)['--night-accent'],
            self::hasDefaultAccent($section) => self::PORTAL_ACCENT['--night-accent'],
            default => self::onDark($section['aksen'], self::PORTAL_NIGHT),
        };

        return ThemePalette::darkenedFor(self::deepened($section['gelap']), ['#FFFFFF', self::NIGHT_MUTED, $nightAccent]);
    }

    /**
     * The portal's dark panels (Irisan 7): the section's panel colour kept
     * dark (no lighter than PANEL_MAX_LIGHTNESS, in its hue), the boxes on
     * it lighter by the built-in steps, each readable under white and the
     * panels' grey text; or the built-in panels.
     *
     * @param  ThemeSection  $section
     * @return array<string, string>
     */
    public static function portalPanel(array $section): array
    {
        if (self::hasDefaultPanel($section)) {
            return self::PORTAL_PANEL;
        }

        $colour = $section['panel'] ?? self::DEFAULT_PANEL;
        [$lightness] = ThemePalette::oklch($colour);
        $base = min($lightness, self::PANEL_MAX_LIGHTNESS);
        $panel = [];

        foreach (self::PANEL_STEPS as $name => $step) {
            $shade = $step === 0 && $lightness <= self::PANEL_MAX_LIGHTNESS ? $colour : ThemePalette::tone($colour, $base + $step, 1, 0.12);
            // White and grey text sit on the panel and its boxes; the lines and the hover of dark buttons carry none of it
            $panel[$name] = in_array($name, ['--night', '--night-2', '--night-3'], true) ? ThemePalette::darkenedFor($shade, ['#FFFFFF', self::NIGHT_MUTED]) : $shade;
        }

        return $panel;
    }

    /**
     * The dashboard's main buttons in light mode: the section's dark, kept
     * dark and readable under white text.
     *
     * @param  ThemeSection  $section
     */
    public static function dashboardDark(array $section): string
    {
        return self::hasDefaultDark($section)
            ? self::DEFAULT_DARK
            : ThemePalette::darkenedFor(self::deepened($section['gelap']), ['#FFFFFF']);
    }

    /**
     * Whether the portal part shows the portal dark for every visitor.
     *
     * @param  ThemeSection  $section
     */
    public static function isDark(array $section): bool
    {
        return ($section['mode'] ?? 'terang') === 'gelap';
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
        $variables = array_merge(
            self::PORTAL_BACKGROUND,
            self::PORTAL_TEXT,
            self::PORTAL_ACCENT,
            self::PORTAL_PANEL,
            [
                '--font-sans' => self::SYSTEM_TEXT_FONT,
                '--font-display' => self::SYSTEM_DISPLAY_FONT,
                '--fw-display' => (string) self::DEFAULT_HEADING_WEIGHT,
                '--r-scale' => '1',
            ],
            self::portal($section),
        );
        // Without a dark of their own, the fills are --ink and the boxes on the navigation --night-2
        $variables['--ink-fill'] ??= $variables['--ink'];
        $variables['--chrome-2'] ??= self::PORTAL_NIGHT[1];
        // The navbar follows the dark of the buttons and navigation until it has a colour of its own
        $variables['--nav-surface'] ??= $variables['--chrome'];
        $variables['--nav-tint'] ??= '255, 255, 255';
        $variables['--nav-cta'] ??= '#FFFFFF';
        $variables['--nav-cta-text'] ??= $variables['--chrome'];

        return array_filter($variables, fn (string $name): bool => str_starts_with($name, '--'), ARRAY_FILTER_USE_KEY);
    }

    /**
     * The dashboard variables the section changes, by where they apply: both
     * modes, light mode only, and dark mode only. The card, dialog and sheet
     * titles take --font-heading, the text font until the section names its
     * own heading font, and --fw-heading.
     *
     * @param  ThemeSection  $section
     * @return array{both: array<string, string>, light: array<string, string>, dark: array<string, string>}
     */
    public static function dashboard(array $section): array
    {
        $background = ThemeBackground::from($section['latar']);
        $backgroundColours = $background->shades(self::DASHBOARD_BACKGROUND);
        $both = self::fonts($section);
        if ($section['hurufJudul'] !== 'sama') {
            $both['--font-heading'] = $both['--font-display'] ?? self::SYSTEM_DISPLAY_FONT;
        }
        if ($section['tebalJudul'] !== self::DEFAULT_HEADING_WEIGHT) {
            $both['--fw-heading'] = (string) $section['tebalJudul'];
        }
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

        if (! self::hasDefaultDark($section)) {
            // The main buttons; in dark mode they stay light
            $light['--primary'] = self::dashboardDark($section);
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
        $navbar = self::navbarContrasts($section, $colours['--chrome']);

        if (self::isDark($section)) {
            return self::contrasts([...[
                'Teks pada latar halaman' => [$colours['--ink'], $colours['--paper']],
                'Teks pendukung pada latar halaman' => [$colours['--text-2'], $colours['--paper']],
                'Aksen pada latar halaman' => [$colours['--accent'], $colours['--paper']],
                'Tautan kecil pada kartu' => [$colours['--accent-ink'], $colours['--surface']],
                'Tautan kecil pada kotak abu' => [$colours['--accent-ink'], $colours['--paper-2']],
                'Teks pada tombol terpilih' => [$colours['--on-ink'], $colours['--ink']],
                'Aksen pada panel gelap' => [$colours['--night-accent'], $colours['--night-2']],
                'Teks abu pada panel gelap' => [self::NIGHT_MUTED, $colours['--night-3']],
                'Teks abu pada navigasi gelap' => [self::NIGHT_MUTED, $colours['--chrome']],
                'Aksen pada navigasi gelap' => [$colours['--night-accent'], $colours['--chrome']],
            ], ...$navbar]);
        }

        return self::contrasts([...[
            'Teks putih pada tombol aksen' => ['#FFFFFF', $colours['--accent']],
            'Aksen pada latar halaman' => [$colours['--accent'], $colours['--paper']],
            'Tautan kecil pada latar halaman' => [$colours['--accent-ink'], $colours['--paper']],
            'Tautan kecil pada kotak abu' => [$colours['--accent-ink'], $colours['--paper-2']],
            'Aksen pada panel gelap' => [$colours['--night-accent'], $colours['--night-2']],
            'Teks abu pada panel gelap' => [self::NIGHT_MUTED, $colours['--night-3']],
            'Teks pendukung pada latar halaman' => ['#646A72', $colours['--paper']],
            'Teks putih pada tombol gelap' => ['#FFFFFF', $colours['--ink-fill']],
            'Teks abu pada navigasi gelap' => [self::NIGHT_MUTED, $colours['--chrome']],
            'Aksen pada navigasi gelap' => [$colours['--night-accent'], $colours['--chrome']],
        ], ...$navbar]);
    }

    /**
     * The navbar's colour pairs, each on the page colour it reads worst on
     * (with glass: the navbar over a white or a black page); none while the
     * navbar is built in.
     *
     * @param  ThemeSection  $section
     * @return array<string, array{string, string}>
     */
    private static function navbarContrasts(array $section, string $chrome): array
    {
        $navbar = self::navbarParts($section, $chrome);

        if ($navbar === null) {
            return [];
        }

        $worst = function (callable $colourOn) use ($navbar): array {
            $pairs = array_map(fn (string $backdrop): array => [$colourOn($backdrop), $backdrop], $navbar['backdrops']);
            usort($pairs, fn (array $a, array $b): int => ThemePalette::contrast(...$a) <=> ThemePalette::contrast(...$b));

            return $pairs[0];
        };

        return [
            'Tautan navbar' => $worst(fn (string $backdrop): string => ThemePalette::blend($navbar['text'], $backdrop, self::NAVBAR_LINK)),
            'Teks redup menu navbar' => $worst(fn (): string => $navbar['muted']),
            'Aksen navbar' => $worst(fn (): string => $navbar['accent']),
        ];
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
        $light = array_merge(self::DASHBOARD_BACKGROUND, self::DASHBOARD_LIGHT_ACCENT, ['--primary' => self::DASHBOARD_PRIMARY], $variables['light']);
        $dark = array_merge(self::DASHBOARD_DARK_ACCENT, $variables['dark']);

        return self::contrasts([
            'Teks putih pada tombol warna merek' => ['#FFFFFF', $light['--brand']],
            'Warna merek pada sidebar' => [$light['--brand'], $light['--sidebar']],
            'Warna merek pada kartu mode gelap' => [$dark['--brand'], self::DASHBOARD_DARK_SURFACES[1]],
            'Teks putih pada tombol utama' => ['#FFFFFF', $light['--primary']],
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
     * The portal's accent colours on its dark mode's backgrounds: lighter,
     * readable on the page, the cards and the grey boxes. No accent fill
     * carries text, so it is safe to lighten.
     *
     * @param  array<string, string>  $dark
     * @return array<string, string>
     */
    private static function portalDarkAccent(string $accent, array $dark): array
    {
        $surfaces = [$dark['--paper'], $dark['--paper-2'], $dark['--surface']];
        $main = self::onDark($accent, $surfaces);
        [$mainLightness] = ThemePalette::oklch($main);
        $soft = ThemePalette::tone($accent, 0.3, 0.3, 0.06);

        return [
            '--accent' => $main,
            '--accent-ink' => ThemePalette::lightenedFor(
                ThemePalette::tone($main, max($mainLightness, 0.8), 0.6, 0.12),
                [...$surfaces, $dark['--paper-3'], $soft],
            ),
            '--accent-soft' => $soft,
            '--accent-line' => ThemePalette::tone($accent, 0.45, 0.5, 0.1),
            '--accent-halo' => ThemePalette::rgba($main, 0.25),
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
     * @param  ThemeSection  $section
     */
    private static function hasDefaultDark(array $section): bool
    {
        return strtoupper($section['gelap']) === self::DEFAULT_DARK;
    }

    /**
     * The navbar, its menus and the mobile menu (Irisan 8) in the navbar's
     * own colour or, built in, the dark of the buttons and navigation: text
     * in white or ink, whichever reads better, a dark logo and dark buttons
     * on a light navbar, and the glass at its level. Nothing while both the
     * colour and the glass are built in.
     *
     * @param  ThemeSection  $section
     * @return array<string, string>
     */
    private static function portalNavbar(array $section, string $chrome, string $darkButton): array
    {
        $navbar = self::navbarParts($section, $chrome);

        if ($navbar === null) {
            return [];
        }

        ['base' => $base, 'ink' => $ink, 'text' => $text, 'alpha' => $alpha, 'backdrops' => $backdrops] = $navbar;
        [$lightness] = ThemePalette::oklch($base);
        $shade = fn (float $step): string => ThemePalette::tone($base, $ink ? $lightness - $step : $lightness + $step, 1, 0.4);

        $variables = [
            '--nav-surface' => $alpha === null ? $base : ThemePalette::rgba($base, $alpha),
            '--nav-tint' => $ink ? '15, 19, 25' : '255, 255, 255',
            '--nav-muted' => $navbar['muted'],
            '--nav-accent' => $navbar['accent'],
            '--nav-2' => $shade(0.05),
            '--nav-line' => $shade(0.105),
        ];
        if ($ink) {
            $variables += [
                '--nav-cta' => $darkButton,
                '--nav-cta-text' => '#FFFFFF',
                '--nav-cta-hover' => ThemePalette::darkenedFor(self::lighter($darkButton, 0.08), ['#FFFFFF']),
            ];
        }
        if ($alpha !== null) {
            $variables['--nav-blur'] = self::NAVBAR_GLASS[$section['kaca'] ?? 'mati'][1].'px';
        }

        return $variables;
    }

    /**
     * The navbar's own colour, darkened or lightened until its links read,
     * or null while it follows the dark of the buttons and navigation.
     *
     * @param  ThemeSection  $section
     */
    public static function navbarColour(array $section): ?string
    {
        $navbar = $section['navbar'] ?? 'gelap';

        if ($navbar === 'gelap') {
            return null;
        }

        $colour = strtoupper($navbar);
        $ink = self::prefersInk($colour);
        $text = $ink ? self::PORTAL_TEXT['--ink'] : '#FFFFFF';

        for ($tries = 0; $tries < 100 && ! self::readsOn(ThemePalette::blend($text, $colour, self::NAVBAR_LINK), [$colour]); $tries++) {
            [$lightness] = ThemePalette::oklch($colour);
            $colour = ThemePalette::tone($colour, $ink ? min(1, $lightness + 0.01) : max(0, $lightness - 0.01), 1, 0.4);
        }

        return $colour;
    }

    /**
     * How much of the page a glass navbar covers once its links read, from
     * 0 to 1; null without glass.
     *
     * @param  ThemeSection  $section
     */
    public static function navbarGlassCover(array $section): ?float
    {
        return self::navbarParts($section, self::portalPreview($section)['--chrome'])['alpha'] ?? null;
    }

    /**
     * What the navbar is made of: its colour, whether ink text reads better
     * than white, its glass cover (null without glass), the page colours its
     * text may sit on (the colour, or the glass over a white and a black
     * page), and the dimmed text and accent readable on all of them.
     *
     * @param  ThemeSection  $section
     * @return array{base: string, ink: bool, text: string, alpha: float|null, backdrops: list<string>, muted: string, accent: string}|null
     */
    private static function navbarParts(array $section, string $chrome): ?array
    {
        $glass = self::NAVBAR_GLASS[$section['kaca'] ?? 'mati'] ?? null;
        $base = self::navbarColour($section);

        if ($base === null && $glass === null) {
            return null;
        }

        $base ??= $chrome;
        $ink = self::prefersInk($base);
        $text = $ink ? self::PORTAL_TEXT['--ink'] : '#FFFFFF';
        $alpha = $glass[0] ?? null;
        $backdrops = fn (?float $cover): array => $cover === null
            ? [$base]
            : [ThemePalette::blend($base, '#FFFFFF', $cover), ThemePalette::blend($base, '#000000', $cover)];

        // Up to no glass at all, where the colour itself reads
        while ($alpha !== null && $alpha < 1 && ! self::linksRead($text, $backdrops($alpha))) {
            $alpha = min(1, $alpha + 0.02);
        }
        $on = $backdrops($alpha);

        $mutedShare = 0.6;
        while ($mutedShare < 1 && ! self::readsOn(ThemePalette::blend($text, $base, $mutedShare), $on)) {
            $mutedShare += 0.04;
        }
        $accent = self::hasDefaultAccent($section) ? self::DEFAULT_ACCENT : $section['aksen'];

        return [
            'base' => $base,
            'ink' => $ink,
            'text' => $text,
            'alpha' => $alpha,
            'backdrops' => $on,
            'muted' => ThemePalette::blend($text, $base, min($mutedShare, 1)),
            'accent' => $ink ? ThemePalette::darkenedFor($accent, $on) : self::onDark($accent, $on),
        ];
    }

    /**
     * Whether ink text reads better on the colour than white.
     */
    private static function prefersInk(string $colour): bool
    {
        return ThemePalette::contrast(self::PORTAL_TEXT['--ink'], $colour) > ThemePalette::contrast('#FFFFFF', $colour);
    }

    /**
     * Whether the navbar's links (its text at 72%) read on every backdrop.
     *
     * @param  list<string>  $backdrops
     */
    private static function linksRead(string $text, array $backdrops): bool
    {
        foreach ($backdrops as $backdrop) {
            if (! self::readsOn(ThemePalette::blend($text, $backdrop, self::NAVBAR_LINK), [$backdrop])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether the colour reads on every background (WCAG AA).
     *
     * @param  list<string>  $backgrounds
     */
    private static function readsOn(string $colour, array $backgrounds): bool
    {
        foreach ($backgrounds as $background) {
            if (ThemePalette::contrast($colour, $background) < ThemePalette::MIN_CONTRAST) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  ThemeSection  $section
     */
    private static function hasDefaultPanel(array $section): bool
    {
        return strtoupper($section['panel'] ?? self::DEFAULT_PANEL) === self::DEFAULT_PANEL;
    }

    /**
     * The accent on panels of another colour, readable on the panel and its
     * boxes, with its transparent fill and ring.
     *
     * @param  ThemeSection  $section
     * @return array<string, string>
     */
    private static function panelAccent(array $section): array
    {
        $panel = self::portalPanel($section);
        $night = self::onDark(
            self::hasDefaultAccent($section) ? self::DEFAULT_ACCENT : $section['aksen'],
            [$panel['--night'], $panel['--night-2'], $panel['--night-3']],
        );

        return [
            '--night-accent' => $night,
            '--night-accent-soft' => ThemePalette::rgba($night, 0.1),
            '--night-accent-ring' => ThemePalette::rgba($night, 0.35),
        ];
    }

    /**
     * The colour as a dark: no lighter than DARK_MAX_LIGHTNESS, in its hue.
     * A colour already dark enough is kept as it is.
     */
    private static function deepened(string $colour): string
    {
        [$lightness] = ThemePalette::oklch($colour);

        return $lightness > self::DARK_MAX_LIGHTNESS ? ThemePalette::tone($colour, self::DARK_MAX_LIGHTNESS, 1, 0.12) : $colour;
    }

    /**
     * A lighter shade of a dark in its hue, for hover states and the boxes
     * on a dark surface.
     */
    private static function lighter(string $colour, float $step): string
    {
        [$lightness] = ThemePalette::oklch($colour);

        return ThemePalette::tone($colour, $lightness + $step, 1, 0.12);
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
