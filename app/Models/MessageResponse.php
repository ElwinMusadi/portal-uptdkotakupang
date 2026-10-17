<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A response or follow-up note on a message, recorded by an employee.
 * Responses are only ever added, never changed. A reply for the sender
 * (untuk_pelapor) also appears on the message's tracking page, without the
 * employee's name (D-058); the others are internal notes.
 */
class MessageResponse extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'untuk_pelapor' => 'boolean',
        ];
    }

    /**
     * The employee who recorded it.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
