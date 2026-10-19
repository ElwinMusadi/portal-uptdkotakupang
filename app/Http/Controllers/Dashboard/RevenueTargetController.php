<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\RecordActivity;
use App\Enums\DashboardModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateRevenueTargetRequest;
use App\Http\Resources\RevenueTargetResource;
use App\Models\RevenueTarget;
use Illuminate\Support\Facades\DB;

/**
 * The yearly revenue target and opening balance of the realisation report
 * (D-068).
 */
class RevenueTargetController extends Controller
{
    /**
     * Save the year's target and opening balance. Saving the same figures
     * again changes and logs nothing.
     */
    public function update(UpdateRevenueTargetRequest $request, int $tahun, RecordActivity $recordActivity): RevenueTargetResource
    {
        $target = RevenueTarget::query()->firstOrNew(['tahun' => $tahun])->fill($request->columns());

        if (! $target->exists || $target->isDirty()) {
            DB::transaction(function () use ($request, $target, $tahun, $recordActivity): void {
                $target->user()->associate($request->user())->save();
                $recordActivity->handle($request->user(), DashboardModule::Penerimaan, 'memperbarui', "Target penerimaan {$tahun}");
            });
        }

        return new RevenueTargetResource($target->load('user'));
    }
}
