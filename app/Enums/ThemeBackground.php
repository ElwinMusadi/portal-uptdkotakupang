<?php

namespace App\Enums;

use App\Support\ThemePalette;

/**
 * The background shade of a theme (D-057): the warm paper of the built-in
 * look, a neutral grey, or a cool blue-grey. A shade keeps each background
 * and line colour's lightness, so text contrast stays the same.
 */
enum ThemeBackground: string
{
    case Hangat = 'hangat';
    case Netral = 'netral';
    case Sejuk = 'sejuk';

    /**
     * The hue of the cool shade, in OKLCH degrees.
     */
    private const float COOL_HUE = 255.0;

    /**
     * The built-in warm colours, each with this shade's hue.
     *
     * @param  array<string, string>  $warmColours
     * @return array<string, string>
     */
    public function shades(array $warmColours): array
    {
        return array_map($this->shade(...), $warmColours);
    }

    /**
     * One built-in warm colour with this shade's hue and the same lightness.
     */
    public function shade(string $warmColour): string
    {
        [$lightness, $chroma] = ThemePalette::oklch($warmColour);

        return match ($this) {
            self::Hangat => strtoupper($warmColour),
            self::Netral => ThemePalette::hex($lightness, 0.0, 0.0),
            self::Sejuk => ThemePalette::hex($lightness, $chroma, self::COOL_HUE),
        };
    }
}
