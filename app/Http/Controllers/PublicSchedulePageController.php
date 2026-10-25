<?php

namespace App\Http\Controllers;

use App\Actions\ShowPublicSchedulePage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicSchedulePageController extends Controller
{
    /**
     * Show the Jadwal page's texts for the public portal (D-080). Query
     * parameters are rejected rather than silently ignored.
     */
    public function __invoke(Request $request, ShowPublicSchedulePage $showSchedulePage): JsonResponse
    {
        if ($request->query() !== []) {
            throw ValidationException::withMessages([
                'query' => 'Parameter kueri tidak didukung.',
            ]);
        }

        return response()->json($showSchedulePage->handle());
    }
}
