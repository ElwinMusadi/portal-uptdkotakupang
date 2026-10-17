<?php

namespace App\Http\Controllers;

use App\Actions\ShowPublicMessage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicMessageStatusController extends Controller
{
    /**
     * The sender's tracking page for a message (D-058), opened by its random
     * code. Query parameters are rejected rather than silently ignored.
     */
    public function __invoke(Request $request, string $kode, ShowPublicMessage $showMessage): JsonResponse
    {
        if ($request->query() !== []) {
            throw ValidationException::withMessages(['query' => 'Parameter kueri tidak didukung.']);
        }

        try {
            return response()->json($showMessage->handle($kode));
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Pesan tidak ditemukan. Periksa kembali tautan lacak Anda, atau lacak dengan nomor tiket di halaman Kontak.'], 404);
        }
    }
}
