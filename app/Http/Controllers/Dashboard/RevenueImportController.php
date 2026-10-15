<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\ImportRevenue;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreRevenueImportRequest;
use App\Http\Resources\RevenueImportResource;
use App\Models\RevenueImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
}
