<?php

namespace App\Http\Controllers;

use App\Actions\BuildPublicPotentialMap;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicPotentialMapController extends Controller
{
    /**
     * The Pemetaan PKB figures of the public portal (D-052 slice 2), for the
     * whole data only: query parameters are rejected rather than silently
     * ignored. Once shown, it also names the columns an Administrator chose
     * for the kecamatan and kelurahan tables (never the collection list's).
     */
    public function __invoke(Request $request, BuildPublicPotentialMap $map): JsonResponse
    {
        if ($request->query() !== []) {
            throw ValidationException::withMessages(['query' => 'Parameter kueri tidak didukung.']);
        }

        $figures = $map->handle();

        return response()->json($figures['tersedia'] ? [...$figures, 'kolom' => Setting::potentialMapColumns()['wilayah']] : $figures);
    }
}
