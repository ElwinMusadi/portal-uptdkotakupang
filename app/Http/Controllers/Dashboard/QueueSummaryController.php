<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\BuildQueueSummary;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ShowQueueSummaryRequest;
use App\Http\Resources\QueueTicketResource;
use App\Models\QueueTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The queue summary of a day or up to 31 days (D-051 slice 4), and the
 * tickets of those days for the CSV download, which carries their plate
 * numbers (the owner's decision).
 */
class QueueSummaryController extends Controller
{
    public function show(ShowQueueSummaryRequest $request, BuildQueueSummary $summary): JsonResponse
    {
        return response()->json($summary->handle($request->from(), $request->until()));
    }

    /**
     * Every ticket printed on those days, in print order.
     */
    public function tickets(ShowQueueSummaryRequest $request): AnonymousResourceCollection
    {
        return QueueTicketResource::collection(
            QueueTicket::query()
                ->with(['service', 'printedBy', 'counter'])
                ->whereBetween('tanggal', [$request->from(), $request->until()])
                ->orderBy('created_at')
                ->orderBy('id')
                ->get()
        );
    }
}
