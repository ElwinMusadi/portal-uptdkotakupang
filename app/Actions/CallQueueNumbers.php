<?php

namespace App\Actions;

use App\Enums\QueueStage;
use App\Enums\QueueTicketStatus;
use App\Models\QueueCounter;
use App\Models\QueueService;
use App\Models\QueueTicket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Calling queue numbers at a counter (Antrean Online slice 2, D-051): the
 * next waiting number of the counter's stage, or a given number; calling it
 * again, putting it aside when the taxpayer is not there, and finishing the
 * stage. A counter calls one number at a time: a number still called there
 * is put aside when the counter calls another (the owner's decision). Calls
 * are what the waiting-room display announces.
 */
class CallQueueNumbers
{
    /**
     * Call the earliest number waiting at the counter's stage among the
     * services it serves, whatever day it was printed. Null when none waits.
     */
    public function next(QueueCounter $counter, User $user): ?QueueTicket
    {
        return DB::transaction(function () use ($counter, $user): ?QueueTicket {
            $ticket = QueueTicket::query()
                ->where('tahap', $counter->tahap)
                ->where('status', QueueTicketStatus::Menunggu)
                ->when($counter->layanan !== [], fn ($query) => $query->whereIn(
                    'queue_service_id',
                    QueueService::query()->whereIn('kode', $counter->layanan)->select('id'),
                ))
                ->orderBy('masuk_tahap_pada')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if ($ticket === null) {
                return null;
            }

            $this->putAsideCalledAt($counter, $user);

            return $this->markCalled($ticket, $counter, $user, 'dipanggil');
        });
    }

    /**
     * Call a given number, e.g. typed or scanned from the documents that
     * reached the counter: one waiting or put aside at the counter's stage,
     * or at an earlier stage of its service, whose stages in between then
     * count as done. Calling the number the counter already calls calls it
     * again.
     *
     * @throws ValidationException when the number cannot be called here
     */
    public function call(QueueTicket $ticket, QueueCounter $counter, User $user): QueueTicket
    {
        return DB::transaction(function () use ($ticket, $counter, $user): QueueTicket {
            $ticket = QueueTicket::query()->with('counter')->lockForUpdate()->findOrFail($ticket->getKey());

            if ($ticket->status === QueueTicketStatus::Dipanggil && $ticket->queue_counter_id === $counter->getKey()) {
                return $this->markCalled($ticket, $counter, $user, 'dipanggil ulang');
            }

            $this->ensureCallable($ticket, $counter);
            $this->putAsideCalledAt($counter, $user);
            $stages = $ticket->stages();
            $passed = array_search($counter->tahap, $stages, true) - array_search($ticket->tahap, $stages, true);
            for ($stage = 0; $stage < $passed; $stage++) {
                $this->finishStage($ticket, $user, $counter, automatic: true);
            }

            return $this->markCalled($ticket, $counter, $user, 'dipanggil');
        });
    }

    /**
     * Call a called number again, e.g. when nobody came.
     *
     * @throws ValidationException when the number is not being called
     */
    public function recall(QueueTicket $ticket, User $user): QueueTicket
    {
        $this->ensureCalled($ticket);

        return DB::transaction(fn (): QueueTicket => $this->markCalled($ticket, $ticket->counter, $user, 'dipanggil ulang'));
    }

    /**
     * Put a called number aside: the taxpayer is not at the counter. It can
     * be called again at any time.
     *
     * @throws ValidationException when the number is not being called
     */
    public function postpone(QueueTicket $ticket, User $user): QueueTicket
    {
        $this->ensureCalled($ticket);

        return DB::transaction(function () use ($ticket, $user): QueueTicket {
            $this->putAside($ticket, $user, automatic: false);

            return $ticket;
        });
    }

    /**
     * Finish a called number's stage: it waits at the next stage of its
     * service, or is done after the last.
     *
     * @throws ValidationException when the number is not being called
     */
    public function finish(QueueTicket $ticket, User $user): QueueTicket
    {
        $this->ensureCalled($ticket);

        return DB::transaction(function () use ($ticket, $user): QueueTicket {
            $this->finishStage($ticket, $user, $ticket->counter, automatic: false);

            return $ticket;
        });
    }

    /**
     * @throws ValidationException
     */
    private function ensureCallable(QueueTicket $ticket, QueueCounter $counter): void
    {
        $stages = $ticket->stages();
        $target = array_search($counter->tahap, $stages, true);
        $current = $ticket->tahap === null ? count($stages) : array_search($ticket->tahap, $stages, true);

        $problem = match (true) {
            $ticket->status === QueueTicketStatus::Batal => "Nomor {$ticket->nomor} sudah dibatalkan.",
            $ticket->status === QueueTicketStatus::Selesai => "Nomor {$ticket->nomor} sudah selesai.",
            $target === false => "Layanan nomor {$ticket->nomor} tidak melewati {$counter->tahap->label()}.",
            $current > $target => "Nomor {$ticket->nomor} sudah melewati {$counter->tahap->label()}.",
            $ticket->status === QueueTicketStatus::Dipanggil => "Nomor {$ticket->nomor} sedang dipanggil di {$ticket->counter?->nama}.",
            default => null,
        };

        if ($problem !== null) {
            throw ValidationException::withMessages(['tiket' => $problem]);
        }
    }

    /**
     * @throws ValidationException
     */
    private function ensureCalled(QueueTicket $ticket): void
    {
        if ($ticket->status !== QueueTicketStatus::Dipanggil || $ticket->counter === null) {
            throw ValidationException::withMessages(['tiket' => "Nomor {$ticket->nomor} sedang tidak dipanggil."]);
        }
    }

    /**
     * Put aside the number the counter calls, if any.
     */
    private function putAsideCalledAt(QueueCounter $counter, User $user): void
    {
        QueueTicket::query()
            ->where('queue_counter_id', $counter->getKey())
            ->where('status', QueueTicketStatus::Dipanggil)
            ->lockForUpdate()
            ->get()
            ->each(fn (QueueTicket $called) => $this->putAside($called, $user, automatic: true));
    }

    private function putAside(QueueTicket $ticket, User $user, bool $automatic): void
    {
        $counterId = $ticket->queue_counter_id;
        $ticket->update(['status' => QueueTicketStatus::Ditunda, 'queue_counter_id' => null]);
        $ticket->events()->create([
            'aksi' => 'ditunda', 'tahap' => $ticket->tahap, 'queue_counter_id' => $counterId, 'otomatis' => $automatic, 'user_id' => $user->getKey(),
        ]);
    }

    private function markCalled(QueueTicket $ticket, QueueCounter $counter, User $user, string $action): QueueTicket
    {
        $ticket->update([
            'status' => QueueTicketStatus::Dipanggil,
            'queue_counter_id' => $counter->getKey(),
            'dipanggil_pada' => now(),
            'jumlah_panggil' => $ticket->jumlah_panggil + 1,
        ]);
        $ticket->events()->create(['aksi' => $action, 'tahap' => $ticket->tahap, 'queue_counter_id' => $counter->getKey(), 'user_id' => $user->getKey()]);

        return $ticket->setRelation('counter', $counter);
    }

    /**
     * Mark the ticket's stage done and move it to the next stage of its
     * service, or finish it after the last.
     */
    private function finishStage(QueueTicket $ticket, User $user, ?QueueCounter $counter, bool $automatic): void
    {
        $stages = $ticket->stages();
        $next = $stages[array_search($ticket->tahap, $stages, true) + 1] ?? null;

        $ticket->events()->create([
            'aksi' => 'tahap selesai', 'tahap' => $ticket->tahap, 'queue_counter_id' => $counter?->getKey(), 'otomatis' => $automatic, 'user_id' => $user->getKey(),
        ]);
        $ticket->update([
            'tahap' => $next,
            'status' => $next instanceof QueueStage ? QueueTicketStatus::Menunggu : QueueTicketStatus::Selesai,
            'queue_counter_id' => null,
            'dipanggil_pada' => null,
            'jumlah_panggil' => 0,
            'masuk_tahap_pada' => now(),
            'selesai_pada' => $next instanceof QueueStage ? null : now(),
        ]);

        if ($next === null) {
            $ticket->events()->create(['aksi' => 'selesai', 'user_id' => $user->getKey()]);
        }
    }
}
