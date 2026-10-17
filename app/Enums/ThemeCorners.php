<?php

namespace App\Enums;

/**
 * How rounded a theme's corners are (D-057). Pills and circles stay round.
 */
enum ThemeCorners: string
{
    case Tegas = 'tegas';
    case Sedang = 'sedang';
    case Membulat = 'membulat';

    /**
     * The factor every corner radius is multiplied by.
     */
    public function factor(): float
    {
        return match ($this) {
            self::Tegas => 0.5,
            self::Sedang => 1.0,
            self::Membulat => 1.35,
        };
    }
}
