<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\ListRevenueCounters;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class RevenueCounterController extends Controller
{
    /**
     * The counters an STS file can be imported for (D-047).
     */
    public function __invoke(ListRevenueCounters $counters): JsonResponse
    {
        return response()->json($counters->handle());
    }
}
