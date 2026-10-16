<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\ImportPotentialVehicles;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\AddPotentialRowsRequest;
use App\Http\Requests\Dashboard\StartPotentialImportRequest;
use App\Http\Resources\PotentialImportResource;
use App\Models\PotentialImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Importing BPAD's potential-tax file for Pemetaan PKB (D-052): the data
 * shown now, starting an import, its parts, finishing it, and deleting the
 * data (administrators).
 */
class PotentialImportController extends Controller
{
    /**
     * The import Pemetaan PKB shows, or null before the first one.
     */
    public function show(): JsonResponse
    {
        $current = PotentialImport::current()?->load('importer');

        return response()->json(['impor' => $current === null ? null : PotentialImportResource::make($current)]);
    }

    /**
     * Start an import; the answer tells how many rows to send per part.
     */
    public function store(StartPotentialImportRequest $request, ImportPotentialVehicles $import): JsonResponse
    {
        $started = $import->start($request->user(), $request->validated('berkas'), (int) $request->validated('jumlah'));

        return response()->json([
            'impor' => PotentialImportResource::make($started->load('importer')),
            'barisPerBagian' => ImportPotentialVehicles::ROWS_PER_PART,
        ], 201);
    }

    /**
     * Save the next part of the file's rows.
     */
    public function rows(AddPotentialRowsRequest $request, PotentialImport $potentialImport, ImportPotentialVehicles $import): JsonResponse
    {
        $saved = $import->add($potentialImport, (int) $request->validated('mulai'), $request->rows());

        return response()->json(['diterima' => $saved->diterima, 'jumlah' => $saved->jumlah]);
    }

    /**
     * Finish the import: it replaces the data before it.
     */
    public function finish(Request $request, PotentialImport $potentialImport, ImportPotentialVehicles $import): PotentialImportResource
    {
        return PotentialImportResource::make($import->finish($potentialImport, $request->user())->load('importer'));
    }

    /**
     * Delete the data Pemetaan PKB shows.
     */
    public function destroy(Request $request, ImportPotentialVehicles $import): Response
    {
        $current = PotentialImport::current();
        abort_if($current === null, 404, 'Belum ada data potensi PKB.');

        $import->delete($current, $request->user());

        return response()->noContent();
    }
}
