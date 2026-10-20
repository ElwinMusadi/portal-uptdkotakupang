<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\ImportRevenue;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreRevenueImportRequest;
use Illuminate\Http\JsonResponse;

class RevenueImportPreviewController extends Controller
{
    /**
     * What importing an STS file would add, without saving anything (D-047):
     * the whole file, the transactions not saved yet, how many are skipped
     * because an earlier import saved them or the file lists them twice, and
     * how many of those skipped get their SKPD form number (D-071).
     */
    public function __invoke(StoreRevenueImportRequest $request, ImportRevenue $importRevenue): JsonResponse
    {
        $transactions = $request->transactions();
        ['baru' => $new, 'dilewati' => $skipped, 'dilengkapi' => $completed] = $importRevenue->compare($transactions);

        return response()->json([
            'loket' => $request->counter(),
            'namaBerkas' => $request->fileName(),
            'berkas' => $importRevenue->summary($transactions),
            'baru' => $importRevenue->summary($new),
            'dilewati' => $skipped,
            'dilengkapi' => count($completed),
        ]);
    }
}
