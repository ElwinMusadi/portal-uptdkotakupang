<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\MarkQueueStages;
use App\Enums\QueueStation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\MarkQueueTicketRequest;
use App\Http\Resources\QueueTicketResource;
use App\Models\QueueTicket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The stations behind the counters in Antrean Online (D-051 slice 3):
 * Penetapan, Server, Pelunasan, Cetak SKPD and Cetak STNK list the numbers
 * at their stage and mark them, by scanning or from the list; Penetapan
 * sends documents to Server for a data change; a marking can be undone.
 */
class QueueStationController extends Controller
{
    /**
     * Numbers listed, at most.
     */
    private const int LISTED = 200;

    /**
     * The numbers at the station, longest waiting first, which the station
     * page asks for every few seconds.
     */
    public function show(QueueStation $pos): AnonymousResourceCollection
    {
        return QueueTicketResource::collection(
            QueueTicket::query()
                ->with(['service', 'printedBy', 'counter'])
                ->where('tahap', $pos->stage())
                ->whereIn('status', $pos->statuses())
                ->orderBy('masuk_tahap_pada')
                ->orderBy('id')
                ->limit(self::LISTED)
                ->get()
        );
    }

    /**
     * Mark a number at the station: its stage is done, or, at Server, its
     * data change.
     */
    public function mark(MarkQueueTicketRequest $request, QueueStation $pos, MarkQueueStages $marks): QueueTicketResource
    {
        $ticket = $pos === QueueStation::Server
            ? $marks->finishDataChange($request->ticket(), $request->user())
            : $marks->finish($request->ticket(), $pos->stage(), $request->user());

        return $this->resource($ticket);
    }

    /**
     * Send a number's documents from Penetapan to Server for a data change.
     */
    public function requireDataChange(Request $request, QueueTicket $queueTicket, MarkQueueStages $marks): QueueTicketResource
    {
        return $this->resource($marks->requireDataChange($queueTicket, $request->user()));
    }

    /**
     * Undo the employee's latest marking of a number.
     */
    public function undo(Request $request, QueueTicket $queueTicket, MarkQueueStages $marks): QueueTicketResource
    {
        return $this->resource($marks->undo($queueTicket, $request->user()));
    }

    private function resource(QueueTicket $ticket): QueueTicketResource
    {
        return QueueTicketResource::make($ticket->load(['service', 'printedBy', 'counter']));
    }
}
