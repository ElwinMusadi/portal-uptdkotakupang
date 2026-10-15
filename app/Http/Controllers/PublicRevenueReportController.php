<?php

namespace App\Http\Controllers;

use App\Actions\BuildPublicRevenueReport;
use App\Http\Requests\ShowPublicRevenueReportRequest;
use Illuminate\Http\JsonResponse;

class PublicRevenueReportController extends Controller
{
    /**
     * The revenue report on the portal's Laporan Penerimaan page, from the
     * imports shown on the portal, as fixed tables with small groups masked
     * (D-048, D-049).
     */
    public function __invoke(ShowPublicRevenueReportRequest $request, BuildPublicRevenueReport $buildReport): JsonResponse
    {
        return response()->json($buildReport->handle($request->from(), $request->until()));
    }
}
