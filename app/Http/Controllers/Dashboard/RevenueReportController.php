<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\BuildRevenueReport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ShowRevenueReportRequest;
use Illuminate\Http\JsonResponse;

class RevenueReportController extends Controller
{
    /**
     * The revenue report of a period with the chosen filters (D-047), in full:
     * the dashboard's report masks nothing.
     */
    public function __invoke(ShowRevenueReportRequest $request, BuildRevenueReport $buildReport): JsonResponse
    {
        return response()->json($buildReport->handle($request->from(), $request->until(), $request->filters()));
    }
}
