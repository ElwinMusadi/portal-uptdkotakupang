<?php

namespace App\Actions;

use App\Enums\QueueStage;
use App\Enums\QueueTicketStatus;
use App\Models\QueueEvent;
use App\Models\QueueTicket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Marking queue tickets at the stations behind the counters (Antrean Online
 * slice 3, D-051): Penetapan, Pelunasan, Cetak SKPD and Cetak STNK mark their
 * stage done, Penetapan can send the documents to the Server officer for a
 * data change, and Server sends them back. A scan marks at once (the owner's
 * decision), so the employee can undo their marking while nothing else has
 * happened to the ticket.
 */
class MarkQueueStages
{
    public function __construct(private AdvanceQueueTicket $advance) {}

    /**
     * Mark the stage done: the ticket waits at the next stage of its
     * service, or is done after the last. The stages before it count as
     * done, even when the ticket was called or put aside at one of them.
     *
     * @throws ValidationException when the ticket cannot reach the stage
     */
    public function finish(QueueTicket $ticket, QueueStage $stage, User $user): QueueTicket
    {
        return DB::transaction(function () use ($ticket, $stage, $user): QueueTicket {
            $ticket = $this->lock($ticket);
            $this->advance->ensureReachable($ticket, $stage);
            $before = $ticket->snapshot();
            $this->advance->passStagesBefore($ticket, $stage, $user, null);
            $this->advance->finishStage($ticket, $user, null, automatic: false, before: $before);

            return $ticket;
        });
    }

    /**
     * Send the documents from Penetapan to the Server officer for a change
     * of the vehicle's data.
     *
     * @throws ValidationException when the ticket cannot reach Penetapan or already waits for a change
     */
    public function requireDataChange(QueueTicket $ticket, User $user): QueueTicket
    {
        return DB::transaction(function () use ($ticket, $user): QueueTicket {
            $ticket = $this->lock($ticket);
            $this->advance->ensureReachable($ticket, QueueStage::Penetapan);
            $before = $ticket->snapshot();
            $this->advance->passStagesBefore($ticket, QueueStage::Penetapan, $user, null);
            $ticket->update(['status' => QueueTicketStatus::PerubahanData]);
            $this->record($ticket, 'perlu perubahan data', $user, $before);

            return $ticket;
        });
    }

    /**
     * The Server officer changed the data: the documents wait at Penetapan
     * again, in their old place.
     *
     * @throws ValidationException when the ticket does not wait for a change
     */
    public function finishDataChange(QueueTicket $ticket, User $user): QueueTicket
    {
        return DB::transaction(function () use ($ticket, $user): QueueTicket {
            $ticket = $this->lock($ticket);

            if ($ticket->status !== QueueTicketStatus::PerubahanData) {
                throw ValidationException::withMessages(['tiket' => "Nomor {$ticket->nomor} tidak sedang menunggu perubahan data."]);
            }

            $before = $ticket->snapshot();
            $ticket->update(['status' => QueueTicketStatus::Menunggu]);
            $this->record($ticket, 'perubahan data selesai', $user, $before);

            return $ticket;
        });
    }

    /**
     * Undo the employee's latest marking of the ticket, when nothing has
     * happened to the ticket since: it goes back to where it was.
     *
     * @throws ValidationException when there is no such marking or the ticket moved on
     */
    public function undo(QueueTicket $ticket, User $user): QueueTicket
    {
        return DB::transaction(function () use ($ticket, $user): QueueTicket {
            $ticket = $this->lock($ticket);
            $marking = QueueEvent::query()
                ->whereBelongsTo($ticket, 'ticket')
                ->where(fn ($query) => $query->whereNotNull('keadaan')->orWhere('aksi', 'diurungkan'))
                ->latest('id')
                ->first();

            if ($marking?->keadaan === null || $marking->user_id !== $user->getKey()) {
                throw ValidationException::withMessages(['tiket' => "Tidak ada tanda Anda pada nomor {$ticket->nomor} yang bisa diurungkan."]);
            }
            if (! $this->sameState($ticket->snapshot(), $marking->keadaan['sesudah'])) {
                throw ValidationException::withMessages(['tiket' => "Nomor {$ticket->nomor} sudah berubah sesudah Anda menandainya, jadi tidak bisa diurungkan."]);
            }

            $ticket->update($marking->keadaan['sebelum']);
            $ticket->events()->create(['aksi' => 'diurungkan', 'tahap' => $marking->tahap, 'keterangan' => $marking->aksi, 'user_id' => $user->getKey()]);

            return $ticket;
        });
    }

    /**
     * Whether two places of a ticket are the same, whatever the order of
     * their keys: MySQL keeps a JSON object's keys in its own order.
     *
     * @param  array<string, mixed>  $now
     * @param  array<string, mixed>  $recorded
     */
    private function sameState(array $now, array $recorded): bool
    {
        ksort($now);
        ksort($recorded);

        return $now === $recorded;
    }

    private function lock(QueueTicket $ticket): QueueTicket
    {
        return QueueTicket::query()->lockForUpdate()->findOrFail($ticket->getKey());
    }

    /**
     * @param  array<string, mixed>  $before
     */
    private function record(QueueTicket $ticket, string $action, User $user, array $before): void
    {
        $ticket->events()->create([
            'aksi' => $action,
            'tahap' => $ticket->tahap,
            'keadaan' => ['sebelum' => $before, 'sesudah' => $ticket->snapshot()],
            'user_id' => $user->getKey(),
        ]);
    }
}
