<?php

namespace App\Models;

use Database\Factories\MobileServiceStopFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One Samsat Keliling stop: where the mobile service is on a weekday and
 * when (D-013). Times are "HH:MM" in WITA.
 */
#[Fillable(['hari', 'mulai', 'selesai', 'lokasi', 'kecamatan', 'layanan', 'catatan', 'aktif'])]
class MobileServiceStop extends Model
{
    /** @use HasFactory<MobileServiceStopFactory> */
    use HasFactory, HasUlids;

    /**
     * The service days, Monday (1) to Saturday (6), as the dashboard numbers
     * them.
     *
     * @var array<int, string>
     */
    public const array DAYS = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hari' => 'integer',
            'aktif' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    /**
     * An empty note is stored as an empty string, as the dashboard sends it.
     *
     * @return Attribute<string, ?string>
     */
    protected function catatan(): Attribute
    {
        return Attribute::make(set: fn (?string $value): string => $value ?? '');
    }

    /**
     * Limit the query to the stops shown on the public portal.
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('aktif', true);
    }

    /**
     * The stop as the dashboard names it, e.g. "Senin · Kantor Kelurahan Oebobo".
     */
    public function title(): string
    {
        return (self::DAYS[$this->hari] ?? "Hari {$this->hari}").' · '.$this->lokasi;
    }
}
