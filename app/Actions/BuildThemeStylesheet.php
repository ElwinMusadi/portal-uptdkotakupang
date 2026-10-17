<?php

namespace App\Actions;

use App\Enums\ThemeFont;
use App\Support\ThemeVariables;

/**
 * The stylesheets /tema-portal.css and /tema-dashboard.css (D-057): the
 * hosted fonts a theme section uses and the variables it changes. A section
 * equal to the built-in PINTU look gives only a comment.
 *
 * @phpstan-import-type ThemeSection from ThemeVariables
 */
class BuildThemeStylesheet
{
    /**
     * The pages the portal's scale leaves alone: the TV display already fills
     * its screen with lengths relative to the viewport.
     */
    private const string SCALED_PAGES = ':root:not(:has([data-react="layar-antrean"]))';

    /**
     * What the portal's dark mode changes beyond its variables: the footer's
     * logo in white. The other logos sit on white badges.
     */
    private const string DARK_RULES = <<<'CSS'
        .footer__logo { content: url("/assets/img/logo-pintu-lockup-putih.png"); }

        CSS;

    /**
     * The badge a portal preview link adds to every portal page, so the
     * administrator does not mistake the unsaved theme for the saved one.
     */
    private const string PREVIEW_BADGE = <<<'CSS'
        /* Pratinjau tema dari Pengaturan › Tampilan: hanya peramban yang membuka tautan pratinjau melihatnya. */
        body::after {
          content: "Pratinjau tema · belum disimpan";
          position: fixed;
          z-index: 2147483647;
          left: 50%;
          bottom: 16px;
          transform: translateX(-50%);
          padding: 8px 14px;
          border-radius: 999px;
          background: #0F1319;
          color: #FFFFFF;
          font: 600 13px/1.3 -apple-system, BlinkMacSystemFont, system-ui, "Segoe UI", Roboto, sans-serif;
          white-space: nowrap;
          box-shadow: 0 10px 30px -12px rgba(15, 19, 25, .45);
          pointer-events: none;
        }

        CSS;

    /**
     * The portal's theme, loaded after the portal's own stylesheets, so its
     * :root block overrides tokens.css. The scale zooms every page but the
     * TV display; the dark mode, set by the administrator, applies to every
     * visitor.
     *
     * @param  ThemeSection  $section
     */
    public function portal(array $section): string
    {
        $variables = ThemeVariables::portal($section);
        $zoom = ThemeVariables::portalZoom($section);

        if ($variables === [] && $zoom === []) {
            return $this->unchanged('portal');
        }

        return $this->header('portal')
            .$this->fonts($section)
            .$this->block(':root', $variables)
            .$this->block(self::SCALED_PAGES, $zoom)
            .(ThemeVariables::isDark($section) ? self::DARK_RULES : '');
    }

    /**
     * An unsaved portal theme as a preview link shows it, with a badge on
     * every page.
     *
     * @param  ThemeSection  $section
     */
    public function portalPreview(array $section): string
    {
        return $this->portal($section).self::PREVIEW_BADGE;
    }

    /**
     * The dashboard's theme. Its selectors outrank index.css's :root and
     * .dark, so they apply whether this stylesheet loads before or after the
     * built one (Vite inserts it later while it serves the dashboard). A
     * scale above 100% applies from 48rem (768px) up: narrower, several
     * dashboard pages would grow wider than the screen.
     *
     * @param  ThemeSection  $section
     */
    public function dashboard(array $section): string
    {
        $variables = ThemeVariables::dashboard($section);
        $fontSize = ThemeVariables::dashboardFontSize($section);

        if ($variables === ['both' => [], 'light' => [], 'dark' => []] && $fontSize === null) {
            return $this->unchanged('dashboard');
        }

        $scale = match (true) {
            $fontSize === null => '',
            $section['skala'] < 100 => $this->block('html:root', ['font-size' => $fontSize]),
            default => "@media (min-width: 48rem) {\n".preg_replace('/^/m', '  ', $this->block('html:root', ['font-size' => $fontSize]))."}\n",
        };

        return $this->header('dashboard')
            .$this->fonts($section)
            .$this->block('html:root', $variables['both'])
            .$this->block('html:root:not(.dark)', $variables['light'])
            .$this->block('html:root.dark', $variables['dark'])
            .$scale;
    }

    private function unchanged(string $part): string
    {
        return "/* Tema {$part} PINTU: tampilan bawaan (D-057). */\n";
    }

    private function header(string $part): string
    {
        return "/* Tema {$part} PINTU, diatur Administrator di Pengaturan › Tampilan (D-057). */\n";
    }

    /**
     * The @font-face rules of the hosted fonts the section uses.
     *
     * @param  ThemeSection  $section
     */
    public function fonts(array $section): string
    {
        return implode('', array_map(
            fn (ThemeFont $font): string => "@font-face {\n"
                ."  font-family: \"{$font->family()}\";\n"
                ."  src: url(\"{$font->file()}\") format(\"woff2\");\n"
                ."  font-weight: {$font->weights()};\n"
                ."  font-style: normal;\n"
                ."  font-display: swap;\n"
                ."}\n",
            ThemeVariables::fontFaces($section),
        ));
    }

    /**
     * @param  array<string, string>  $variables
     */
    private function block(string $selector, array $variables): string
    {
        if ($variables === []) {
            return '';
        }

        $lines = array_map(fn (string $name, string $value): string => "  {$name}: {$value};\n", array_keys($variables), $variables);

        return "{$selector} {\n".implode('', $lines)."}\n";
    }
}
