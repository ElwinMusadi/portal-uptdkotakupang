<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A response or follow-up note on a message, recorded by an employee.
 * Responses are only ever added, never changed.
 */
class MessageResponse extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

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
