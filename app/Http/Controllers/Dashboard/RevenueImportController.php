<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\ImportRevenue;
use App\Actions\RecordActivity;
use App\Enums\DashboardModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreRevenueImportRequest;
use App\Http\Requests\Dashboard\UpdateRevenueImportRequest;
use App\Http\Resources\RevenueImportResource;
use App\Models\RevenueImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * The STS files imported for Laporan Penerimaan (D-047).
 */
class RevenueImportController extends Controller
{
    /**
     * Every import, latest first.
     */
    public function index(): AnonymousResourceCollection
    {
        return RevenueImportResource::collection(RevenueImport::query()->with('user')->latest()->orderByDesc('id')->get());
    }

    /**
     * Save the new transactions of an STS file for a counter.
     */
    public function store(StoreRevenueImportRequest $request, ImportRevenue $importRevenue): JsonResponse
    {
        $import = $importRevenue->handle($request->user(), $request->counter(), $request->fileName(), $request->transactions());

        return (new RevenueImportResource($import->load('user')))->response()->setStatusCode(201);
    }

    /**
     * Show the import on the portal or take it off (D-048). Saving the same
     * choice again changes and logs nothing.
     */
    public function update(UpdateRevenueImportRequest $request, RevenueImport $revenueImport, RecordActivity $recordActivity): RevenueImportResource
    {
        $show = $request->boolean('tampil');

        if ($revenueImport->tampil !== $show) {
            DB::transaction(function () use ($request, $revenueImport, $recordActivity, $show): void {
                $revenueImport->update(['tampil' => $show]);
                $recordActivity->handle(
                    $request->user(),
                    DashboardModule::Penerimaan,
                    $show ? 'menampilkan' : 'menyembunyikan',
                    $revenueImport->title().($show ? ' di portal' : ' dari portal'),
                );
            });
        }

        return new RevenueImportResource($revenueImport->load('user'));
    }
}
