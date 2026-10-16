<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\ImportPotentialVehicles;
use App\Actions\RecordActivity;
use App\Enums\DashboardModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\AddPotentialRowsRequest;
use App\Http\Requests\Dashboard\StartPotentialImportRequest;
use App\Http\Requests\Dashboard\UpdatePotentialImportRequest;
use App\Http\Resources\PotentialImportResource;
use App\Models\PotentialImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Importing BPAD's potential-tax file for Pemetaan PKB (D-052): the data
 * shown now, starting an import, its parts, finishing it, showing it on the
 * portal and deleting the data (administrators).
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
     * Show the figures of the data shown now on the public portal or take
     * them off (D-052 slice 2). Saving the same choice again changes and
     * logs nothing.
     */
    public function update(UpdatePotentialImportRequest $request, ImportPotentialVehicles $import, RecordActivity $recordActivity): PotentialImportResource
    {
        $current = PotentialImport::current();
        abort_if($current === null, 404, 'Belum ada data potensi PKB.');
        $show = $request->boolean('tampil');

        if ($current->tampil !== $show) {
            DB::transaction(function () use ($request, $current, $import, $recordActivity, $show): void {
                $current->update(['tampil' => $show]);
                $recordActivity->handle(
                    $request->user(),
                    DashboardModule::Pemetaan,
                    $show ? 'menampilkan' : 'menyembunyikan',
                    $import->title($current).($show ? ' di portal' : ' dari portal'),
                );
            });
        }

        return PotentialImportResource::make($current->load('importer'));
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
