<?php

namespace App\Enums;

/**
 * The kinds of service outside the office (D-043): the three Samsat
 * Keliling units, which serve at a place for one day; fixed outlets such as
 * Samsat Corner and Mall Pelayanan Publik, open on the same days every week;
 * and temporary counters for a season or an event.
 */
enum ServicePointKind: string
{
    case Keliling = 'keliling';
    case Gerai = 'gerai';
    case Sementara = 'sementara';

    /**
     * The kind as the dashboard and the portal name it.
     */
    public function label(): string
    {
        return match ($this) {
            self::Keliling => 'Samsat Keliling',
            self::Gerai => 'Gerai Tetap',
            self::Sementara => 'Loket Sementara',
        };
    }
}
