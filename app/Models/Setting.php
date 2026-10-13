<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One dashboard settings document stored on the server, keyed by the
 * dashboard's setting name (D-014). Settings not stored here still live in
 * the browser.
 */
class Setting extends Model
{
    /**
     * The opening hours of the office counters (Jam Pelayanan).
     */
    public const string OPENING_HOURS = 'jam';

    /**
     * The service board on the portal's homepage (Papan Layanan).
     */
    public const string SERVICE_BOARD = 'papan';

    /**
     * The rates of the PKB simulation and the Kalkulator PKB (Tarif & Simulasi).
     */
    public const string TARIFF = 'tarif';

    /**
     * The tariff until it is first saved: every rate is zero, so nothing is
     * simulated with rates nobody has set.
     *
     * @var array{opsenPersen: int, dendaPersenPerBulan: int, maksBulanDenda: int, swdklljMotor: int, swdklljMobil: int, contohPokok: int, contohJenis: string, contohTelat: int, catatan: string}
     */
    public const array UNSAVED_TARIFF = [
        'opsenPersen' => 0,
        'dendaPersenPerBulan' => 0,
        'maksBulanDenda' => 0,
        'swdklljMotor' => 0,
        'swdklljMobil' => 0,
        'contohPokok' => 0,
        'contohJenis' => 'motor',
        'contohTelat' => 0,
        'catatan' => '',
    ];

    protected $primaryKey = 'name';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    /**
     * The opening hours as the dashboard edits them. Until they are first
     * saved, every day is closed.
     *
     * @return array{hari: list<array{hari: int, buka: bool, mulai: string, selesai: string, catatan: string}>, batasBerkasMenit: int, catatan: string, pemberitahuan: string}
     */
    public static function openingHours(): array
    {
        return self::query()->find(self::OPENING_HOURS)?->value ?? [
            'hari' => array_map(
                fn (int $hari): array => ['hari' => $hari, 'buka' => false, 'mulai' => '', 'selesai' => '', 'catatan' => ''],
                [1, 2, 3, 4, 5, 6, 0],
            ),
            'batasBerkasMenit' => 60,
            'catatan' => '',
            'pemberitahuan' => '',
        ];
    }

    /**
     * The service board as the dashboard edits it. Until it is first saved it
     * is empty and "diperbarui" is null.
     *
     * @return array{nomorAntrean: string, layananAntrean: string, waktuTunggu: int, selisihKemarin: int, loket: list<array{id: string, kode: string, nama: string, status: string}>, catatan: string, diperbarui: ?string}
     */
    public static function serviceBoard(): array
    {
        return self::query()->find(self::SERVICE_BOARD)?->value ?? [
            'nomorAntrean' => '',
            'layananAntrean' => '',
            'waktuTunggu' => 0,
            'selisihKemarin' => 0,
            'loket' => [],
            'catatan' => '',
            'diperbarui' => null,
        ];
    }

    /**
     * The tariff as the dashboard edits it.
     *
     * @return array{opsenPersen: int|float, dendaPersenPerBulan: int|float, maksBulanDenda: int, swdklljMotor: int, swdklljMobil: int, contohPokok: int, contohJenis: string, contohTelat: int, catatan: string}
     */
    public static function tariff(): array
    {
        return self::query()->find(self::TARIFF)?->value ?? self::UNSAVED_TARIFF;
    }
}
