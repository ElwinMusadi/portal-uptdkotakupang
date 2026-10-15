<?php

namespace App\Models;

use App\Enums\QueueStage;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One thing that happened to a queue ticket (D-051): printed, printed again,
 * cancelled, and later called or a stage finished, with the employee and the
 * stage. A station's marking keeps the ticket's place before and after it,
 * for undoing it.
 *
 * @property ?QueueStage $tahap
 * @property ?array{sebelum: array<string, mixed>, sesudah: array<string, mixed>} $keadaan
 */
#[Fillable(['aksi', 'tahap', 'keterangan', 'queue_counter_id', 'otomatis', 'keadaan', 'user_id'])]
class QueueEvent extends Model
{
    /**
     * Events are written once and never updated.
     */
    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tahap' => QueueStage::class,
            'otomatis' => 'boolean',
            'keadaan' => 'array',
        ];
    }

    /**
     * The counter of a call.
     *
     * @return BelongsTo<QueueCounter, $this>
     */
    public function counter(): BelongsTo
    {
        return $this->belongsTo(QueueCounter::class, 'queue_counter_id');
    }

    /**
     * The ticket the event belongs to.
     *
     * @return BelongsTo<QueueTicket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(QueueTicket::class, 'queue_ticket_id');
    }

    /**
     * The employee who acted.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
