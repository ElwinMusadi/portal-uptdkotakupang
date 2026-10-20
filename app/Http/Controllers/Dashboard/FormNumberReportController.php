<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\BuildFormNumberReport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ShowFormNumberReportRequest;
use Illuminate\Http\JsonResponse;

class FormNumberReportController extends Controller
{
    /**
     * The SKPD form numbers used in a day or a month, per counter and day
     * (Nomoratur, D-071).
     */
    public function __invoke(ShowFormNumberReportRequest $request, BuildFormNumberReport $buildReport): JsonResponse
    {
        return response()->json($buildReport->handle($request->from(), $request->until(), $request->counter()));
    }
}
