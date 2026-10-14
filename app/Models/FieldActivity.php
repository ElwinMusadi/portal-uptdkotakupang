<?php

namespace App\Models;

use Database\Factories\FieldActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A field activity such as a joint traffic operation (Absensi Lapangan,
 * D-034). Administrators set where it takes place and how far from that
 * point officers may clock in, and assign the officers. Clock-in is open
 * on the activity's date in WITA; officers clock out with the fingerprint
 * reader in the field, outside PINTU.
 *
 * @property Carbon $tanggal
 * @property float $lat
 * @property float $lng
 */
#[Fillable(['nama', 'tanggal', 'tempat', 'lat', 'lng', 'radius', 'catatan'])]
class FieldActivity extends Model
{
    /** @use HasFactory<FieldActivityFactory> */
    use HasFactory, HasUlids;

    /**
     * The time zone of the activity's date.
     */
    public const string TIME_ZONE = 'Asia/Makassar';

    /**
     * Closest and widest radius an administrator may set, in metres.
     */
    public const int MIN_RADIUS = 20;

    public const int MAX_RADIUS = 2000;

    /**
     * Least accurate GPS position accepted for clock-in, in metres.
     */
    public const int MAX_ACCURACY = 100;

    /**
     * A new activity has no note.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'catatan' => '',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'lat' => 'float',
            'lng' => 'float',
            'radius' => 'integer',
        ];
    }

    /**
     * The officers assigned to the activity.
     *
     * @return BelongsToMany<User, $this>
     */
    public function officers(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * The officers' clock-ins.
     *
     * @return HasMany<FieldAttendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(FieldAttendance::class);
    }

    /**
     * Whether clock-in is open: the activity takes place today in WITA.
     */
    public function isOpen(): bool
    {
        return $this->tanggal->toDateString() === now(self::TIME_ZONE)->toDateString();
    }

    /**
     * Distance in whole metres from the activity's point to the given one,
     * along the earth's surface (haversine).
     */
    public function distanceTo(float $lat, float $lng): int
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat - $this->lat);
        $dLng = deg2rad($lng - $this->lng);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($this->lat)) * cos(deg2rad($lat)) * sin($dLng / 2) ** 2;

        return (int) round(2 * $earthRadius * asin(min(1, sqrt($a))));
    }
}
