<?php

namespace App\Http\Controllers;

use App\Actions\ShowPublicTariff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicTariffController extends Controller
{
    /**
     * Show the tariff for the portal's PKB simulation. Query parameters are
     * rejected rather than silently ignored.
     */
    public function __invoke(Request $request, ShowPublicTariff $showTariff): JsonResponse
    {
        if ($request->query() !== []) {
            throw ValidationException::withMessages([
                'query' => 'Parameter kueri tidak didukung.',
            ]);
        }

        return response()->json($showTariff->handle());
    }
}
