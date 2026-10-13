<?php

namespace App\Models;

use Database\Factories\ScheduleAdjustmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A change to the opening hours on one date: the counters are closed
 * ("tutup") or open at special hours ("jam-khusus") (D-014). Times are
 * "HH:MM" in WITA and empty when closed.
 */
#[Fillable(['tanggal', 'jenis', 'mulai', 'selesai', 'keterangan'])]
class ScheduleAdjustment extends Model
{
    /** @use HasFactory<ScheduleAdjustmentFactory> */
    use HasFactory, HasUlids;

    public const string CLOSED = 'tutup';

    public const string SPECIAL_HOURS = 'jam-khusus';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
        ];
    }

    /**
     * The date as "YYYY-MM-DD", whatever the database returns.
     *
     * @return Attribute<string, string>
     */
    protected function tanggal(): Attribute
    {
        return Attribute::make(get: fn (string $value): string => substr($value, 0, 10));
    }

    /**
     * Times are only kept for special hours.
     */
    protected static function booted(): void
    {
        static::saving(function (ScheduleAdjustment $adjustment): void {
            if ($adjustment->jenis === self::CLOSED) {
                $adjustment->mulai = '';
                $adjustment->selesai = '';
            }
        });
    }

    /**
     * The adjustment as the dashboard names it, e.g. "2026-12-25 · Hari Raya Natal".
     */
    public function title(): string
    {
        return "{$this->tanggal} · {$this->keterangan}";
    }
}
