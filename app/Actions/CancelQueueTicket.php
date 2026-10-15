<?php

namespace App\Actions;

use App\Enums\QueueTicketStatus;
use App\Models\QueueTicket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Cancel a queue ticket with the reason (D-051), e.g. documents turned back
 * at the stage or a taxpayer who left. The ticket keeps the stage it stopped
 * at.
 */
class CancelQueueTicket
{
    /**
     * @throws ValidationException when the ticket is already cancelled or done
     */
    public function handle(QueueTicket $ticket, string $reason, User $user): QueueTicket
    {
        if ($ticket->status->isClosed()) {
            throw ValidationException::withMessages([
                'tiket' => $ticket->status === QueueTicketStatus::Batal ? "Nomor {$ticket->nomor} sudah dibatalkan." : "Nomor {$ticket->nomor} sudah selesai.",
            ]);
        }

        return DB::transaction(function () use ($ticket, $reason, $user): QueueTicket {
            $ticket->update([
                'status' => QueueTicketStatus::Batal,
                'alasan_batal' => $reason,
                'dibatalkan_pada' => now(),
                'queue_counter_id' => null,
            ]);
            $ticket->events()->create([
                'aksi' => 'dibatalkan',
                'tahap' => $ticket->tahap,
                'keterangan' => $reason,
                'user_id' => $user->getKey(),
            ]);

            return $ticket;
        });
    }
}
