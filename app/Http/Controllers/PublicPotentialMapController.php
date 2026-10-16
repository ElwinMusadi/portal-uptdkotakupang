<?php

namespace App\Http\Controllers;

use App\Actions\BuildPublicPotentialMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicPotentialMapController extends Controller
{
    /**
     * The Pemetaan PKB figures of the public portal (D-052 slice 2), for the
     * whole data only: query parameters are rejected rather than silently
     * ignored.
     */
    public function __invoke(Request $request, BuildPublicPotentialMap $map): JsonResponse
    {
        if ($request->query() !== []) {
            throw ValidationException::withMessages(['query' => 'Parameter kueri tidak didukung.']);
        }

        return response()->json($map->handle());
    }
}
