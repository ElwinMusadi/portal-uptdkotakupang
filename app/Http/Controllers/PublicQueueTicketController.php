<?php

namespace App\Http\Controllers;

use App\Actions\ShowPublicQueueTicket;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicQueueTicketController extends Controller
{
    /**
     * The taxpayer's tracking page for the queue ticket behind a QR (D-051).
     * Only the random code opens a ticket; there is no search. Query
     * parameters are rejected rather than silently ignored.
     */
    public function __invoke(Request $request, string $kode, ShowPublicQueueTicket $showTicket): JsonResponse
    {
        if ($request->query() !== []) {
            throw ValidationException::withMessages(['query' => 'Parameter kueri tidak didukung.']);
        }

        try {
            return response()->json($showTicket->handle($kode));
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Nomor antrean tidak ditemukan. Periksa kembali QR pada tiket Anda.'], 404);
        }
    }
}
