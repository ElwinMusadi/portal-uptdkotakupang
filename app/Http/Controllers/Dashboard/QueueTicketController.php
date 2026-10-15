<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\CancelQueueTicket;
use App\Actions\PrintQueueTicket;
use App\Enums\QueueTicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\CancelQueueTicketRequest;
use App\Http\Requests\Dashboard\ListQueueTicketsRequest;
use App\Http\Requests\Dashboard\SearchQueueTicketsRequest;
use App\Http\Requests\Dashboard\StoreQueueTicketRequest;
use App\Http\Resources\QueueTicketResource;
use App\Models\QueueTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

/**
 * Queue numbers at the information desk (Antrean Online, D-051): the day's
 * list, printing, printing again and cancelling.
 */
class QueueTicketController extends Controller
{
    /**
     * The numbers printed on a day, latest first.
     */
    public function index(ListQueueTicketsRequest $request): AnonymousResourceCollection
    {
        return QueueTicketResource::collection(
            QueueTicket::query()->with(['service', 'printedBy', 'counter'])->where('tanggal', $request->day())->latest()->orderByDesc('id')->get()
        );
    }

    /**
     * The open numbers matching what a counter typed or scanned: the
     * ticket's QR, its number, or the plate number; today's first, at most
     * ten.
     */
    public function search(SearchQueueTicketsRequest $request): AnonymousResourceCollection
    {
        $tickets = $request->whereTyped(QueueTicket::query())
            ->with(['service', 'printedBy', 'counter'])
            ->whereNotIn('status', [QueueTicketStatus::Selesai, QueueTicketStatus::Batal])
            ->orderByDesc('tanggal')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return QueueTicketResource::collection($tickets);
    }

    /**
     * Print the next number of the service.
     */
    public function store(StoreQueueTicketRequest $request, PrintQueueTicket $printTicket): JsonResponse
    {
        $ticket = $printTicket->handle($request->service(), $request->validated('nopol'), $request->user());

        return QueueTicketResource::make($ticket->load(['service', 'printedBy', 'counter']))->response()->setStatusCode(201);
    }

    /**
     * Print a ticket again, e.g. when the paper jammed: same number, same QR.
     */
    public function reprint(Request $request, QueueTicket $queueTicket): QueueTicketResource
    {
        if ($queueTicket->status === QueueTicketStatus::Batal) {
            throw ValidationException::withMessages(['tiket' => "Nomor {$queueTicket->nomor} sudah dibatalkan dan tidak bisa dicetak ulang."]);
        }

        $queueTicket->events()->create(['aksi' => 'dicetak ulang', 'tahap' => $queueTicket->tahap, 'user_id' => $request->user()->getKey()]);

        return QueueTicketResource::make($queueTicket->load(['service', 'printedBy', 'counter']));
    }

    /**
     * Cancel a number with the reason.
     */
    public function cancel(CancelQueueTicketRequest $request, QueueTicket $queueTicket, CancelQueueTicket $cancelTicket): QueueTicketResource
    {
        $ticket = $cancelTicket->handle($queueTicket, $request->validated('alasan'), $request->user());

        return QueueTicketResource::make($ticket->load(['service', 'printedBy', 'counter']));
    }
}
