<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\DeleteEntries;
use App\Enums\DashboardModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\DeleteScheduleAdjustmentsRequest;
use App\Models\ScheduleAdjustment;
use Illuminate\Http\Response;

class ScheduleAdjustmentDeletionController extends Controller
{
    /**
     * Delete the listed adjustments (ScheduleAdjustmentPolicy).
     */
    public function store(DeleteScheduleAdjustmentsRequest $request, DeleteEntries $deleteEntries): Response
    {
        $deleteEntries->handle(
            $request->user(),
            DashboardModule::Jam,
            ScheduleAdjustment::class,
            $request->ids(),
            fn (ScheduleAdjustment $adjustment): string => $adjustment->title(),
        );

        return response()->noContent();
    }
}
