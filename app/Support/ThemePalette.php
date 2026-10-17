<?php

namespace App\Support;

/**
 * Colour arithmetic for the themes (D-057): OKLCH, the perceptual colour
 * space the derived colours are computed in, and the WCAG contrast ratio
 * they are checked against. Colours are "#RRGGBB" strings.
 */
final class ThemePalette
{
    /**
     * The WCAG AA contrast ratio for normal text.
     */
    public const float MIN_CONTRAST = 4.5;

    /**
     * The colour in OKLCH: lightness 0–1, chroma, and hue in degrees.
     *
     * @return array{float, float, float}
     */
    public static function oklch(string $hex): array
    {
        [$r, $g, $b] = array_map(self::toLinear(...), self::channels($hex));

        $l = self::cbrt(0.4122214708 * $r + 0.5363325363 * $g + 0.0514459929 * $b);
        $m = self::cbrt(0.2119034982 * $r + 0.6806995451 * $g + 0.1073969566 * $b);
        $s = self::cbrt(0.0883024619 * $r + 0.2817188376 * $g + 0.6299787005 * $b);

        $lightness = 0.2104542553 * $l + 0.7936177850 * $m - 0.0040720468 * $s;
        $a = 1.9779984951 * $l - 2.4285922050 * $m + 0.4505937099 * $s;
        $bb = 0.0259040371 * $l + 0.7827717662 * $m - 0.8086757660 * $s;

        $hue = rad2deg(atan2($bb, $a));

        return [$lightness, sqrt($a * $a + $bb * $bb), $hue < 0 ? $hue + 360 : $hue];
    }

    /**
     * The OKLCH colour as "#RRGGBB". A colour outside the sRGB gamut keeps
     * its lightness and hue and loses chroma until it fits.
     */
    public static function hex(float $lightness, float $chroma, float $hue): string
    {
        $lightness = max(0.0, min(1.0, $lightness));

        if (self::linearRgb($lightness, $chroma, $hue) === null) {
            [$low, $high] = [0.0, $chroma];
            for ($i = 0; $i < 24; $i++) {
                $middle = ($low + $high) / 2;
                if (self::linearRgb($lightness, $middle, $hue) === null) {
                    $high = $middle;
                } else {
                    $low = $middle;
                }
            }
            $chroma = $low;
        }

        $rgb = self::linearRgb($lightness, $chroma, $hue) ?? [$lightness, $lightness, $lightness];

        return '#'.implode('', array_map(
            fn (float $channel): string => sprintf('%02X', (int) round(self::fromLinear($channel) * 255)),
            $rgb,
        ));
    }

    /**
     * The WCAG contrast ratio of two colours, from 1 to 21.
     */
    public static function contrast(string $first, string $second): float
    {
        [$lighter, $darker] = [self::luminance($first), self::luminance($second)];
        if ($lighter < $darker) {
            [$lighter, $darker] = [$darker, $lighter];
        }

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * The colour, unchanged when it reaches the ratio against every colour in
     * $against; otherwise the lightest darker shade of the same hue that does.
     *
     * @param  list<string>  $against
     */
    public static function darkenedFor(string $hex, array $against, float $ratio = self::MIN_CONTRAST): string
    {
        return self::adjustedFor($hex, $against, $ratio, darker: true);
    }

    /**
     * The colour, unchanged when it reaches the ratio against every colour in
     * $against; otherwise the darkest lighter shade of the same hue that does.
     *
     * @param  list<string>  $against
     */
    public static function lightenedFor(string $hex, array $against, float $ratio = self::MIN_CONTRAST): string
    {
        return self::adjustedFor($hex, $against, $ratio, darker: false);
    }

    /**
     * A shade of the colour's hue at the given lightness, with a share of its
     * chroma up to a cap; a grey stays grey.
     */
    public static function tone(string $hex, float $lightness, float $chromaShare, float $maxChroma): string
    {
        [, $chroma, $hue] = self::oklch($hex);

        return self::hex($lightness, min($chroma * $chromaShare, $maxChroma), $hue);
    }

    /**
     * The colour a browser shows for $top at $alpha over $bottom, mixed per
     * sRGB channel: text at 72% on the navbar, a glass navbar over the page.
     */
    public static function blend(string $top, string $bottom, float $alpha): string
    {
        $mixed = array_map(
            fn (int $over, int $under): int => (int) round($over * $alpha + $under * (1 - $alpha)),
            self::channels($top, scale: 255),
            self::channels($bottom, scale: 255),
        );

        return vsprintf('#%02X%02X%02X', $mixed);
    }

    /**
     * The colour with transparency, written like the portal's tokens:
     * "rgba(43, 99, 246, .18)".
     */
    public static function rgba(string $hex, float $alpha): string
    {
        return sprintf('rgba(%s, %s)', implode(', ', self::channels($hex, scale: 255)), ltrim(rtrim(rtrim(sprintf('%.2f', $alpha), '0'), '.'), '0'));
    }

    /**
     * @param  list<string>  $against
     */
    private static function adjustedFor(string $hex, array $against, float $ratio, bool $darker): string
    {
        $reaches = fn (string $colour): bool => array_reduce(
            $against,
            fn (bool $carry, string $background): bool => $carry && self::contrast($colour, $background) >= $ratio,
            true,
        );

        if ($reaches($hex)) {
            return strtoupper($hex);
        }

        [$lightness, $chroma, $hue] = self::oklch($hex);
        // The shade that reaches the ratio sits at $passing; the original lightness fails
        [$passing, $failing] = $darker ? [0.0, $lightness] : [1.0, $lightness];

        for ($i = 0; $i < 30; $i++) {
            $middle = ($passing + $failing) / 2;
            if ($reaches(self::hex($middle, $chroma, $hue))) {
                $passing = $middle;
            } else {
                $failing = $middle;
            }
        }

        return self::hex($passing, $chroma, $hue);
    }

    /**
     * The colour's red, green and blue, from 0 to 1, or 0 to 255 when scaled.
     *
     * @return array{float|int, float|int, float|int}
     */
    private static function channels(string $hex, int $scale = 1): array
    {
        $values = array_map(fn (string $pair): int => (int) hexdec($pair), str_split(ltrim($hex, '#'), 2));

        return $scale === 255 ? [$values[0], $values[1], $values[2]] : [$values[0] / 255, $values[1] / 255, $values[2] / 255];
    }

    /**
     * The colour's linear red, green and blue, or null outside the sRGB gamut.
     *
     * @return array{float, float, float}|null
     */
    private static function linearRgb(float $lightness, float $chroma, float $hue): ?array
    {
        $a = $chroma * cos(deg2rad($hue));
        $b = $chroma * sin(deg2rad($hue));

        $l = ($lightness + 0.3963377774 * $a + 0.2158037573 * $b) ** 3;
        $m = ($lightness - 0.1055613458 * $a - 0.0638541728 * $b) ** 3;
        $s = ($lightness - 0.0894841775 * $a - 1.2914855480 * $b) ** 3;

        $rgb = [
            4.0767416621 * $l - 3.3077115913 * $m + 0.2309699292 * $s,
            -1.2684380046 * $l + 2.6097574011 * $m - 0.3413193965 * $s,
            -0.0041960863 * $l - 0.7034186147 * $m + 1.7076147010 * $s,
        ];

        foreach ($rgb as $channel) {
            if ($channel < -0.0001 || $channel > 1.0001) {
                return null;
            }
        }

        return array_map(fn (float $channel): float => max(0.0, min(1.0, $channel)), $rgb);
    }

    private static function luminance(string $hex): float
    {
        [$r, $g, $b] = array_map(self::toLinear(...), self::channels($hex));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    private static function toLinear(float $channel): float
    {
        return $channel <= 0.04045 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
    }

    private static function fromLinear(float $channel): float
    {
        return $channel <= 0.0031308 ? 12.92 * $channel : 1.055 * $channel ** (1 / 2.4) - 0.055;
    }

    private static function cbrt(float $value): float
    {
        return $value < 0 ? -((-$value) ** (1 / 3)) : $value ** (1 / 3);
    }
}
