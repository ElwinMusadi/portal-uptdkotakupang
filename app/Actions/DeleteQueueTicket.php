<?php

namespace App\Actions;

use App\Enums\DashboardModule;
use App\Models\QueueTicket;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Delete a queue ticket with its history for good (D-051 slice 4; the
 * owner's decision: administrators delete whole tickets). Only a finished or
 * cancelled ticket of an earlier day goes: one still running is cancelled
 * first, and today's numbers stay so that the next print never repeats a
 * number already on paper. The activity log keeps the number and date,
 * without the plate number.
 */
class DeleteQueueTicket
{
    public function __construct(private RecordActivity $recordActivity) {}

    /**
     * @throws ValidationException when the ticket is still running or printed today
     */
    public function handle(QueueTicket $ticket, User $user): void
    {
        $problem = match (true) {
            ! $ticket->status->isClosed() => "Nomor {$ticket->nomor} masih berjalan. Batalkan dulu sebelum menghapusnya.",
            $ticket->tanggal >= now(QueueTicket::TIME_ZONE)->toDateString() => "Nomor {$ticket->nomor} dicetak hari ini dan baru bisa dihapus mulai besok, agar nomornya tidak terpakai dua kali.",
            default => null,
        };

        if ($problem !== null) {
            throw ValidationException::withMessages(['tiket' => $problem]);
        }

        DB::transaction(function () use ($ticket, $user): void {
            $ticket->delete();
            $printed = Carbon::parse($ticket->tanggal)->locale('id')->translatedFormat('j M Y');
            $this->recordActivity->handle($user, DashboardModule::Antrean, 'menghapus', "Nomor antrean {$ticket->nomor} ({$printed})");
        });
    }
}
