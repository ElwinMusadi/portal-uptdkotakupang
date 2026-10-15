<?php

namespace App\Http\Controllers;

use App\Actions\ShowQueueDisplay;
use App\Models\QueueDisplay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueueDisplayDataController extends Controller
{
    /**
     * What a waiting-room TV display shows (D-051), for a display holding the
     * token of its setup link as a bearer token. It asks every few seconds;
     * the time it was last seen is kept to the minute.
     */
    public function __invoke(Request $request, ShowQueueDisplay $showDisplay): JsonResponse
    {
        $token = $request->bearerToken();
        $display = $token === null ? null : QueueDisplay::query()->where('token_hash', QueueDisplay::hash($token))->first();

        if ($display === null) {
            return response()->json(['message' => 'Layar ini belum dipasang atau sudah dicabut.'], 401);
        }

        if ($display->terakhir_terlihat === null || $display->terakhir_terlihat->lt(now()->subMinute())) {
            $display->forceFill(['terakhir_terlihat' => now()])->save();
        }

        return response()->json($showDisplay->handle());
    }
}
