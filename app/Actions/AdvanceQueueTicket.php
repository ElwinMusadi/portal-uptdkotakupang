<?php

namespace App\Actions;

use App\Enums\QueueStage;
use App\Enums\QueueTicketStatus;
use App\Models\QueueCounter;
use App\Models\QueueTicket;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Moving a queue ticket along its service's stages (D-051), shared by the
 * counters that call numbers and the stations that mark them: whether a
 * stage can still be reached, finishing the ticket's stage, and passing the
 * stages before a later one. Scanning is optional, so a stage that nobody
 * marked counts as done on its own when a later counter or station takes
 * the ticket (the owner's decision).
 */
class AdvanceQueueTicket
{
    /**
     * @throws ValidationException when the ticket cannot reach the stage
     */
    public function ensureReachable(QueueTicket $ticket, QueueStage $stage): void
    {
        $stages = $ticket->stages();
        $target = array_search($stage, $stages, true);
        $current = $ticket->tahap === null ? count($stages) : array_search($ticket->tahap, $stages, true);

        $problem = match (true) {
            $ticket->status === QueueTicketStatus::Batal => "Nomor {$ticket->nomor} sudah dibatalkan.",
            $ticket->status === QueueTicketStatus::Selesai => "Nomor {$ticket->nomor} sudah selesai.",
            $target === false => "Layanan nomor {$ticket->nomor} tidak melewati {$stage->label()}.",
            $current > $target => "Nomor {$ticket->nomor} sudah melewati {$stage->label()}.",
            $ticket->status === QueueTicketStatus::PerubahanData => "Nomor {$ticket->nomor} sedang menunggu perubahan data di Server.",
            default => null,
        };

        if ($problem !== null) {
            throw ValidationException::withMessages(['tiket' => $problem]);
        }
    }

    /**
     * Count the stages before the given one as done, each recorded as passed
     * on its own.
     */
    public function passStagesBefore(QueueTicket $ticket, QueueStage $stage, User $user, ?QueueCounter $counter): void
    {
        $stages = $ticket->stages();
        $passed = array_search($stage, $stages, true) - array_search($ticket->tahap, $stages, true);

        for ($step = 0; $step < $passed; $step++) {
            $this->finishStage($ticket, $user, $counter, automatic: true);
        }
    }

    /**
     * Mark the ticket's stage done and move it to the next stage of its
     * service, or finish it after the last. A station's marking keeps the
     * ticket's place before it, and after, so it can be undone.
     *
     * @param  ?array<string, mixed>  $before
     */
    public function finishStage(QueueTicket $ticket, User $user, ?QueueCounter $counter, bool $automatic, ?array $before = null): void
    {
        $stages = $ticket->stages();
        $finished = $ticket->tahap;
        $next = $stages[array_search($finished, $stages, true) + 1] ?? null;

        $ticket->update([
            'tahap' => $next,
            'status' => $next instanceof QueueStage ? QueueTicketStatus::Menunggu : QueueTicketStatus::Selesai,
            'queue_counter_id' => null,
            'dipanggil_pada' => null,
            'jumlah_panggil' => 0,
            'masuk_tahap_pada' => now(),
            'selesai_pada' => $next instanceof QueueStage ? null : now(),
        ]);
        $ticket->events()->create([
            'aksi' => 'tahap selesai',
            'tahap' => $finished,
            'queue_counter_id' => $counter?->getKey(),
            'otomatis' => $automatic,
            'keadaan' => $before === null ? null : ['sebelum' => $before, 'sesudah' => $ticket->snapshot()],
            'user_id' => $user->getKey(),
        ]);

        if ($next === null) {
            $ticket->events()->create(['aksi' => 'selesai', 'user_id' => $user->getKey()]);
        }
    }
}
