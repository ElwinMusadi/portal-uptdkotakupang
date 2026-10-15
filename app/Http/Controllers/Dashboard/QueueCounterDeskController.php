<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\CallQueueNumbers;
use App\Enums\QueueTicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\CallQueueTicketRequest;
use App\Http\Resources\QueueCounterResource;
use App\Http\Resources\QueueTicketResource;
use App\Models\QueueCounter;
use App\Models\QueueService;
use App\Models\QueueTicket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * A counter's desk in Antrean Online (D-051): the number it calls, the
 * numbers waiting at its stage for the services it serves, and those put
 * aside; calling the next or a given number.
 */
class QueueCounterDeskController extends Controller
{
    /**
     * Numbers listed per kind, at most.
     */
    private const int LISTED = 200;

    /**
     * The counter's desk, which the counter page asks for every few seconds.
     */
    public function show(QueueCounter $queueCounter): JsonResponse
    {
        $atStage = fn (QueueTicketStatus $status): Builder => QueueTicket::query()
            ->with(['service', 'printedBy', 'counter'])
            ->where('tahap', $queueCounter->tahap)
            ->where('status', $status)
            ->when($queueCounter->layanan !== [], fn (Builder $query) => $query->whereIn(
                'queue_service_id',
                QueueService::query()->whereIn('kode', $queueCounter->layanan)->select('id'),
            ));

        $called = QueueTicket::query()
            ->with(['service', 'printedBy', 'counter'])
            ->where('queue_counter_id', $queueCounter->getKey())
            ->where('status', QueueTicketStatus::Dipanggil)
            ->first();

        return response()->json([
            'loket' => QueueCounterResource::make($queueCounter),
            'dipanggil' => $called === null ? null : QueueTicketResource::make($called),
            'menunggu' => QueueTicketResource::collection($atStage(QueueTicketStatus::Menunggu)->orderBy('masuk_tahap_pada')->orderBy('id')->limit(self::LISTED)->get()),
            'ditunda' => QueueTicketResource::collection($atStage(QueueTicketStatus::Ditunda)->orderBy('dipanggil_pada')->orderBy('id')->limit(self::LISTED)->get()),
        ]);
    }

    /**
     * Call the next waiting number, or the given one, to the counter.
     */
    public function call(CallQueueTicketRequest $request, QueueCounter $queueCounter, CallQueueNumbers $calls): QueueTicketResource
    {
        if (! $queueCounter->aktif) {
            throw ValidationException::withMessages(['loket' => "{$queueCounter->nama} sedang tidak dipakai."]);
        }

        $ticket = $request->ticket();
        $called = $ticket === null ? $calls->next($queueCounter, $request->user()) : $calls->call($ticket, $queueCounter, $request->user());

        if ($called === null) {
            throw ValidationException::withMessages(['loket' => "Tidak ada nomor yang menunggu di {$queueCounter->nama}."]);
        }

        return QueueTicketResource::make($called->load(['service', 'printedBy', 'counter']));
    }
}
