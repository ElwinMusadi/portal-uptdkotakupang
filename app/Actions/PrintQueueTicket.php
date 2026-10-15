<?php

namespace App\Actions;

use App\Enums\QueueTicketStatus;
use App\Models\QueueService;
use App\Models\QueueTicket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Print a queue number at the information desk once the documents are
 * complete (Antrean Online, D-051): the next number of the service on the
 * WITA day, e.g. A-001, waiting at the service's first stage.
 */
class PrintQueueTicket
{
    /**
     * Number the ticket. The service row is locked while the next number is
     * taken, so two desks printing at once never get the same number.
     */
    public function handle(QueueService $service, string $plate, User $user): QueueTicket
    {
        return DB::transaction(function () use ($service, $plate, $user): QueueTicket {
            $service = QueueService::query()->lockForUpdate()->findOrFail($service->getKey());
            $date = now(QueueTicket::TIME_ZONE)->toDateString();
            $number = (int) QueueTicket::query()->where('queue_service_id', $service->getKey())->where('tanggal', $date)->max('urut') + 1;

            $ticket = QueueTicket::query()->create([
                'kode_lacak' => Str::random(QueueTicket::TRACKING_CODE_LENGTH),
                'queue_service_id' => $service->getKey(),
                'tanggal' => $date,
                'urut' => $number,
                'nomor' => sprintf('%s-%03d', $service->kode, $number),
                'nopol' => $plate,
                'tahap_layanan' => $service->tahap,
                'tahap' => $service->stages()[0],
                'status' => QueueTicketStatus::Menunggu,
                'masuk_tahap_pada' => now(),
                'dicetak_oleh' => $user->getKey(),
            ]);
            $ticket->events()->create(['aksi' => 'dicetak', 'user_id' => $user->getKey()]);

            return $ticket;
        });
    }
}
