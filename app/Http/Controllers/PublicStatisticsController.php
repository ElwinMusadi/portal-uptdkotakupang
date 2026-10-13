<?php

namespace App\Http\Controllers;

use App\Actions\ShowPublicStatistics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicStatisticsController extends Controller
{
    /**
     * Show the verified statistics for the portal's transparency dashboard.
     * Query parameters are rejected rather than silently ignored.
     */
    public function __invoke(Request $request, ShowPublicStatistics $showStatistics): JsonResponse
    {
        if ($request->query() !== []) {
            throw ValidationException::withMessages([
                'query' => 'Parameter kueri tidak didukung.',
            ]);
        }

        return response()->json($showStatistics->handle());
    }
}
