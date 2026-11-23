<?php

namespace App\Enums;

/**
 * Which transactions a realisation report (Form I) counts (D-068, D-101):
 * every one paid at the city's counters, of any regency ("gabungan", Form I),
 * or only those of vehicles registered at the city's UPT, the STS column
 * UPT ASAL ("Kota Kupang", Form I.I). Both share the year's target; each has
 * its own opening balance over the same months.
 */
enum RealizationScope: string
{
    case Combined = 'gabungan';
    case KotaKupang = 'kota-kupang';

    /**
     * The city's UPT as the STS column UPT ASAL writes it.
     */
    public const string CITY_UPT = 'Kota Kupang';

    /**
     * The revenue report filters that keep the transactions counted.
     *
     * @return array<string, list<string>>
     */
    public function filters(): array
    {
        return match ($this) {
            self::Combined => [],
            self::KotaKupang => ['uptAsal' => [self::CITY_UPT]],
        };
    }

    /**
     * The column of the opening balance of an STS amount, e.g. "saldo_pkb".
     */
    public function balanceColumn(string $part): string
    {
        return match ($this) {
            self::Combined => "saldo_{$part}",
            self::KotaKupang => "saldo_kota_{$part}",
        };
    }

    /**
     * The label in the top-left corner above the table, as the office's
     * forms write it; Form I.I has none.
     */
    public function label(): ?string
    {
        return match ($this) {
            self::Combined => 'GABUNGAN',
            self::KotaKupang => null,
        };
    }

    /**
     * The form's number in the top-right corner.
     */
    public function form(): string
    {
        return match ($this) {
            self::Combined => 'Form. I',
            self::KotaKupang => 'Form I.I',
        };
    }

    /**
     * The heading over the three realisation columns.
     */
    public function heading(): string
    {
        return match ($this) {
            self::Combined => 'REALISASI PENERIMAAN GABUNGAN',
            self::KotaKupang => 'REALISASI PENERIMAAN KOTA KUPANG',
        };
    }

    /**
     * The workbook's name before the month, e.g.
     * "realisasi-penerimaan-kota-kupang".
     */
    public function fileName(): string
    {
        return match ($this) {
            self::Combined => 'realisasi-penerimaan',
            self::KotaKupang => 'realisasi-penerimaan-kota-kupang',
        };
    }
}
