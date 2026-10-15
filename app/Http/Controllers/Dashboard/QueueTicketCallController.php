<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\CallQueueNumbers;
use App\Http\Controllers\Controller;
use App\Http\Resources\QueueTicketResource;
use App\Models\QueueTicket;
use Illuminate\Http\Request;

/**
 * What a counter does with the number it called (D-051): call it again, put
 * it aside, or finish its stage.
 */
class QueueTicketCallController extends Controller
{
    public function recall(Request $request, QueueTicket $queueTicket, CallQueueNumbers $calls): QueueTicketResource
    {
        return $this->resource($calls->recall($queueTicket, $request->user()));
    }

    public function postpone(Request $request, QueueTicket $queueTicket, CallQueueNumbers $calls): QueueTicketResource
    {
        return $this->resource($calls->postpone($queueTicket, $request->user()));
    }

    public function finish(Request $request, QueueTicket $queueTicket, CallQueueNumbers $calls): QueueTicketResource
    {
        return $this->resource($calls->finish($queueTicket, $request->user()));
    }

    private function resource(QueueTicket $ticket): QueueTicketResource
    {
        return QueueTicketResource::make($ticket->load(['service', 'printedBy', 'counter']));
    }
}
