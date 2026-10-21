<?php

namespace App\Enums;

/**
 * The pattern on the portal's page background (D-073): none, as built in,
 * or dots like 21st.dev's DotPattern. It covers the pages with the shared
 * header and scrolls with them; cards and panels keep their own colours.
 */
enum ThemePattern: string
{
    case Polos = 'polos';
    case Titik = 'titik';

    /**
     * One dot in each 16px square, at (1, 1) with a 1px radius, as in
     * DotPattern. Its grey is neutral-400 on the light paper and neutral-600
     * on the dark one.
     */
    private const string DOT = "<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16'><circle cx='1' cy='1' r='1' fill='rgba(%s,%s)'/></svg>";

    private const string DOT_LIGHT = '163,163,163';

    private const string DOT_DARK = '82,82,82';

    /**
     * The dots' opacity at 100% clarity (D-074): the look of D-073, about
     * 1.9 : 1 against either paper. 40% (0.32) gives about 1.3 : 1.
     */
    private const float FULL_OPACITY = 0.8;

    /**
     * The background declarations of the pattern at a clarity in percent;
     * none for the plain page.
     *
     * @return array<string, string>
     */
    public function declarations(bool $dark, int $clarity): array
    {
        $opacity = round(self::FULL_OPACITY * $clarity / 100, 2);

        return match ($this) {
            self::Polos => [],
            self::Titik => [
                'background-image' => 'url("data:image/svg+xml,'.strtr(sprintf(self::DOT, $dark ? self::DOT_DARK : self::DOT_LIGHT, $opacity), ['<' => '%3C', '>' => '%3E']).'")',
                'background-size' => '16px 16px',
            ],
        };
    }
}
