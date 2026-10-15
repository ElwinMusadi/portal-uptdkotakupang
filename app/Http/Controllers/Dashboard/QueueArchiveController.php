<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\DeleteQueueTicket;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ArchiveQueueTicketsRequest;
use App\Http\Resources\QueueTicketResource;
use App\Models\QueueService;
use App\Models\QueueTicket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * The queue archive (D-051 slice 4): every ticket ever printed, kept with its
 * plate number and history to find documents or tickets gone astray. Staff
 * of the module search it and read a ticket's history; administrators delete
 * finished tickets.
 */
class QueueArchiveController extends Controller
{
    /**
     * Tickets listed per search, at most.
     */
    public const int LISTED = 200;

    /**
     * The newest tickets matching the search; each part is optional.
     */
    public function index(ArchiveQueueTicketsRequest $request): AnonymousResourceCollection
    {
        $query = QueueTicket::query()->with(['service', 'printedBy', 'counter']);

        if ($request->hasTyped()) {
            $request->whereTyped($query, partOfPlate: true);
        }

        $tickets = $query
            ->when($request->validated('dari'), fn (Builder $query, string $from) => $query->where('tanggal', '>=', $from))
            ->when($request->validated('sampai'), fn (Builder $query, string $until) => $query->where('tanggal', '<=', $until))
            ->when($request->validated('layanan'), fn (Builder $query, string $code) => $query->whereIn(
                'queue_service_id',
                QueueService::query()->where('kode', $code)->select('id'),
            ))
            ->when($request->validated('status'), fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($request->validated('tahap'), fn (Builder $query, string $stage) => $query->where('tahap', $stage))
            ->orderByDesc('tanggal')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::LISTED)
            ->get();

        return QueueTicketResource::collection($tickets);
    }

    /**
     * A ticket with its whole history, the employees and counters included.
     */
    public function show(QueueTicket $queueTicket): QueueTicketResource
    {
        return QueueTicketResource::make($queueTicket->load(['service', 'printedBy', 'counter', 'events.user', 'events.counter']));
    }

    /**
     * Delete a finished or cancelled ticket of an earlier day with its history.
     */
    public function destroy(Request $request, QueueTicket $queueTicket, DeleteQueueTicket $deleteTicket): Response
    {
        $deleteTicket->handle($queueTicket, $request->user());

        return response()->noContent();
    }
}
