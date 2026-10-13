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
}
