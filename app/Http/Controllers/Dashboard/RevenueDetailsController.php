<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\BuildRevenueDetails;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ShowRevenueDetailsRequest;
use App\Http\Resources\RevenueDetailResource;
use Illuminate\Http\JsonResponse;

class RevenueDetailsController extends Controller
{
    /**
     * Rincian penerimaan (D-079): the transactions of one of the last 7 days
     * with the report's filters, the plate number, the owner's name and the
     * file's other columns included, and how many lack them.
     */
    public function __invoke(ShowRevenueDetailsRequest $request, BuildRevenueDetails $buildDetails): JsonResponse
    {
        ['transaksi' => $transactions, 'belumTercatat' => $missing] = $buildDetails->handle($request->day(), $request->filters());

        return response()->json([
            'tanggal' => $request->day()->toDateString(),
            'transaksi' => RevenueDetailResource::collection($transactions),
            'belumTercatat' => $missing,
        ]);
    }
}
