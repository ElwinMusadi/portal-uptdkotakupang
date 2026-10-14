<?php

namespace App\Models;

use Database\Factories\FieldAttendanceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An officer's clock-in at a field activity (D-034): the server's time,
 * the GPS point and its accuracy, the distance to the activity's point,
 * and the selfie in the private bucket. Clock-ins are never changed.
 *
 * @property Carbon $waktu
 */
class FieldAttendance extends Model
{
    /** @use HasFactory<FieldAttendanceFactory> */
    use HasFactory, HasUlids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'waktu' => 'datetime',
            'lat' => 'float',
            'lng' => 'float',
            'akurasi' => 'integer',
            'jarak' => 'integer',
        ];
    }

    /**
     * The activity clocked in to.
     *
     * @return BelongsTo<FieldActivity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(FieldActivity::class, 'field_activity_id');
    }

    /**
     * The officer who clocked in.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
