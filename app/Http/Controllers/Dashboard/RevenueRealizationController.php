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
 * The realisation report of a month against the year's target: Form I of
 * every counter's STS transactions (D-068), or Form I.I of the vehicles
 * registered at the city's UPT (D-101).
 */
class RevenueRealizationController extends Controller
{
    /**
     * The month's report, through the given day when there is one (D-070),
     * with the year's target and opening balance, or null when the year has
     * none yet.
     */
    public function show(ShowRevenueRealizationRequest $request, BuildRevenueRealization $buildRealization): JsonResponse
    {
        $target = $this->target($request->year());

        return response()->json([
            ...$buildRealization->handle($request->year(), $request->month(), $target, $request->until(), $request->scope()),
            'target' => $target === null ? null : new RevenueTargetResource($target),
        ]);
    }

    /**
     * The month's report as an Excel workbook laid out as Form I or Form I.I,
     * named after the form and the day it stops at when there is one, e.g.
     * "realisasi-penerimaan-2026-10-sd-07.xlsx" or
     * "realisasi-penerimaan-kota-kupang-2026-10.xlsx".
     *
     * @throws ValidationException when the opening balance covers the month or, for Form I.I, is not entered yet
     */
    public function download(
        ShowRevenueRealizationRequest $request,
        BuildRevenueRealization $buildRealization,
        WriteRevenueRealizationWorkbook $writeWorkbook,
    ): BinaryFileResponse {
        $until = $request->until();
        $scope = $request->scope();
        $report = $buildRealization->handle($request->year(), $request->month(), $this->target($request->year()), $until, $scope);

        if (! $report['tersedia']) {
            throw ValidationException::withMessages(['bulan' => 'Realisasi bulan ini tercatat dalam saldo awal, jadi tidak ada laporan per bulannya.']);
        }

        if ($report['saldoKosong']) {
            throw ValidationException::withMessages(['cakupan' => 'Isi saldo awal Kota Kupang di Target lebih dulu.']);
        }

        $name = sprintf('%s-%04d-%02d%s.xlsx', $scope->fileName(), $report['tahun'], $report['bulan'], $until === null ? '' : '-sd-'.$until->format('d'));

        return response()->download($writeWorkbook->handle($report), $name, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    private function target(int $year): ?RevenueTarget
    {
        return RevenueTarget::query()->with('user')->firstWhere('tahun', $year);
    }
}
