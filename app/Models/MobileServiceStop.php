<?php

namespace App\Models;

use App\Enums\ServicePointKind;
use Carbon\CarbonInterface;
use Database\Factories\MobileServiceStopFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One schedule entry of service outside the office (D-013, widened by
 * D-043): a Samsat Keliling unit at a place on one date, a fixed outlet on
 * its weekdays, or a temporary counter on its weekdays within a date range.
 * Every entry occurs on the weekdays in `hari` (0 = Sunday) between its
 * optional first and last date. Times are "HH:MM" in WITA. The class keeps
 * its original name from when only Samsat Keliling was scheduled.
 *
 * @property ServicePointKind $jenis
 * @property ?Carbon $tanggal_mulai
 * @property ?Carbon $tanggal_selesai
 * @property list<int> $hari
 */
#[Fillable(['jenis', 'unit', 'nama', 'tanggal_mulai', 'tanggal_selesai', 'hari', 'mulai', 'selesai', 'lokasi', 'kecamatan', 'layanan', 'catatan', 'aktif'])]
class MobileServiceStop extends Model
{
    /** @use HasFactory<MobileServiceStopFactory> */
    use HasFactory, HasUlids;

    /**
     * The weekdays, as the dashboard and JavaScript number them.
     *
     * @var array<int, string>
     */
    public const array DAYS = [0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];

    /**
     * The Samsat Keliling units.
     *
     * @var list<string>
     */
    public const array UNITS = ['01', '02', '03'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenis' => ServicePointKind::class,
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'hari' => 'array',
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
     * Limit the query to the entries shown on the public portal.
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('aktif', true);
    }

    /**
     * Limit the query to the entries whose dates reach into the given days.
     */
    #[Scope]
    protected function within(Builder $query, CarbonInterface $from, CarbonInterface $until): void
    {
        $query
            ->where(fn (Builder $query) => $query->whereNull('tanggal_mulai')->orWhereDate('tanggal_mulai', '<=', $until->toDateString()))
            ->where(fn (Builder $query) => $query->whereNull('tanggal_selesai')->orWhereDate('tanggal_selesai', '>=', $from->toDateString()));
    }

    /**
     * Whether the entry is served on the given date.
     */
    public function occursOn(CarbonInterface $date): bool
    {
        $day = $date->toDateString();

        return ($this->tanggal_mulai === null || $this->tanggal_mulai->toDateString() <= $day)
            && ($this->tanggal_selesai === null || $this->tanggal_selesai->toDateString() >= $day)
            && in_array($date->dayOfWeek, $this->hari, true);
    }

    /**
     * Who serves: the Samsat Keliling unit, or the outlet or counter.
     */
    public function name(): string
    {
        return $this->jenis === ServicePointKind::Keliling ? "Samsat Keliling {$this->unit}" : $this->nama;
    }

    /**
     * The entry as the activity log names it, e.g. "Samsat Keliling 01 ·
     * Sabtu, 2026-10-17 · CFD Eltari" or "Samsat Corner · Lippo Plaza".
     */
    public function title(): string
    {
        if ($this->jenis === ServicePointKind::Keliling && $this->tanggal_mulai !== null) {
            return "{$this->name()} · ".self::DAYS[$this->tanggal_mulai->dayOfWeek].", {$this->tanggal_mulai->toDateString()} · {$this->lokasi}";
        }

        return "{$this->name()} · {$this->lokasi}";
    }
}
