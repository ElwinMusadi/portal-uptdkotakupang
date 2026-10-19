<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\BuildRevenueRealization;
use App\Actions\WriteRevenueRealizationWorkbook;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ShowRevenueRealizationRequest;
use App\Http\Resources\RevenueTargetResource;
use App\Models\RevenueTarget;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The realisation report of a month against the year's target, Form I
 * (D-068): every counter's STS transactions, in full.
 */
class RevenueRealizationController extends Controller
{
    /**
     * The month's report with the year's target and opening balance, or
     * null when the year has none yet.
     */
    public function show(ShowRevenueRealizationRequest $request, BuildRevenueRealization $buildRealization): JsonResponse
    {
        $target = $this->target($request->year());

        return response()->json([
            ...$buildRealization->handle($request->year(), $request->month(), $target),
            'target' => $target === null ? null : new RevenueTargetResource($target),
        ]);
    }

    /**
     * The month's report as an Excel workbook laid out as Form I.
     *
     * @throws ValidationException when the opening balance covers the month
     */
    public function download(
        ShowRevenueRealizationRequest $request,
        BuildRevenueRealization $buildRealization,
        WriteRevenueRealizationWorkbook $writeWorkbook,
    ): BinaryFileResponse {
        $report = $buildRealization->handle($request->year(), $request->month(), $this->target($request->year()));

        if (! $report['tersedia']) {
            throw ValidationException::withMessages(['bulan' => 'Realisasi bulan ini tercatat dalam saldo awal, jadi tidak ada laporan per bulannya.']);
        }

        $name = sprintf('realisasi-penerimaan-%04d-%02d.xlsx', $report['tahun'], $report['bulan']);

        return response()->download($writeWorkbook->handle($report), $name, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    private function target(int $year): ?RevenueTarget
    {
        return RevenueTarget::query()->with('user')->firstWhere('tahun', $year);
    }
}
