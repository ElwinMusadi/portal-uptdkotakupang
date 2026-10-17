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
     * The portal's theme, loaded after the portal's own stylesheets, so its
     * :root block overrides tokens.css.
     *
     * @param  ThemeSection  $section
     */
    public function portal(array $section): string
    {
        $variables = ThemeVariables::portal($section);

        if ($variables === []) {
            return $this->unchanged('portal');
        }

        return $this->header('portal').$this->fonts($section).$this->block(':root', $variables);
    }

    /**
     * The dashboard's theme. Its selectors outrank index.css's :root and
     * .dark, so they apply whether this stylesheet loads before or after the
     * built one (Vite inserts it later while it serves the dashboard).
     *
     * @param  ThemeSection  $section
     */
    public function dashboard(array $section): string
    {
        $variables = ThemeVariables::dashboard($section);

        if ($variables === ['both' => [], 'light' => [], 'dark' => []]) {
            return $this->unchanged('dashboard');
        }

        return $this->header('dashboard')
            .$this->fonts($section)
            .$this->block('html:root', $variables['both'])
            .$this->block('html:root:not(.dark)', $variables['light'])
            .$this->block('html:root.dark', $variables['dark']);
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
