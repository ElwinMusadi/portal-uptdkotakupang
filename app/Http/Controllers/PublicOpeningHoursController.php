<?php

namespace App\Http\Controllers;

use App\Actions\ShowPublicOpeningHours;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicOpeningHoursController extends Controller
{
    /**
     * Show the opening hours and coming adjustments for the public portal.
     * Query parameters are rejected rather than silently ignored.
     */
    public function __invoke(Request $request, ShowPublicOpeningHours $showOpeningHours): JsonResponse
    {
        if ($request->query() !== []) {
            throw ValidationException::withMessages([
                'query' => 'Parameter kueri tidak didukung.',
            ]);
        }

        return response()->json($showOpeningHours->handle());
    }
}
