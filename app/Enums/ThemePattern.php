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
     * DotPattern. Its grey is neutral-400 at 80% on the light paper and
     * neutral-600 at 80% on the dark one, about 1.9 : 1 against either.
     */
    private const string DOT = "<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16'><circle cx='1' cy='1' r='1' fill='%s'/></svg>";

    private const string DOT_LIGHT = 'rgba(163,163,163,0.8)';

    private const string DOT_DARK = 'rgba(82,82,82,0.8)';

    /**
     * The background declarations of the pattern; none for the plain page.
     *
     * @return array<string, string>
     */
    public function declarations(bool $dark): array
    {
        return match ($this) {
            self::Polos => [],
            self::Titik => [
                'background-image' => 'url("data:image/svg+xml,'.strtr(sprintf(self::DOT, $dark ? self::DOT_DARK : self::DOT_LIGHT), ['<' => '%3C', '>' => '%3E']).'")',
                'background-size' => '16px 16px',
            ],
        };
    }
}
