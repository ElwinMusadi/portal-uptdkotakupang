<?php

namespace App\Http\Controllers;

use App\Actions\ListPublicMobileServiceStops;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicMobileServiceStopController extends Controller
{
    /**
     * The public schedule of service outside the office (D-043): Samsat
     * Keliling, fixed outlets and temporary counters. It is complete and
     * unpaginated, so query parameters are rejected rather than silently
     * ignored.
     */
    public function __invoke(Request $request, ListPublicMobileServiceStops $listStops): JsonResponse
    {
        if ($request->query() !== []) {
            throw ValidationException::withMessages([
                'query' => 'Parameter kueri tidak didukung.',
            ]);
        }

        return response()->json($listStops->handle());
    }
}
