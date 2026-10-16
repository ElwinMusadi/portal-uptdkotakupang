<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\BuildPotentialSummary;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ShowPotentialSummaryRequest;
use App\Http\Resources\PotentialImportResource;
use App\Models\PotentialImport;
use Illuminate\Http\JsonResponse;

/**
 * The Pemetaan PKB summary of the data shown now (D-052), for the map and
 * the tables of the dashboard.
 */
class PotentialSummaryController extends Controller
{
    public function __invoke(ShowPotentialSummaryRequest $request, BuildPotentialSummary $summary): JsonResponse
    {
        $current = PotentialImport::current()?->load('importer');

        if ($current === null) {
            return response()->json(['impor' => null]);
        }

        return response()->json([
            'impor' => PotentialImportResource::make($current),
            'bulanDipilih' => $request->month(),
            ...$summary->handle($current, $request->month()),
        ]);
    }
}
