<?php

namespace App\Enums;

/**
 * Where a vehicle of the potential-tax file stands with its tax (D-052),
 * grouped from BPAD's "kepatuhan" text: paid or not yet due, due today, late
 * by less than a year, or in arrears for one year or more.
 */
enum TaxCompliance: string
{
    /** "Taat Membayar Pajak": paid, or due later this period. */
    case Taat = 'taat';
    /** "Pajak Berlaku Sampai Dengan Hari Ini". */
    case JatuhTempo = 'jatuh-tempo';
    /** "Terlambat Membayar N Hari/Bulan": late by less than a year. */
    case Terlambat = 'terlambat';
    /** "Menunggak N Tahun": in arrears for a year or more. */
    case Menunggak = 'menunggak';
    /** Any other text. */
    case Lainnya = 'lainnya';

    /**
     * The group of a "kepatuhan" text.
     */
    public static function fromText(string $text): self
    {
        return match (true) {
            str_starts_with($text, 'Taat') => self::Taat,
            str_starts_with($text, 'Pajak Berlaku Sampai') => self::JatuhTempo,
            str_starts_with($text, 'Terlambat') => self::Terlambat,
            str_starts_with($text, 'Menunggak') => self::Menunggak,
            default => self::Lainnya,
        };
    }

    /**
     * Whether the vehicle still has tax to pay.
     */
    public function owes(): bool
    {
        return $this !== self::Taat;
    }
}
