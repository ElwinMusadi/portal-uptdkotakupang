<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\RecordActivity;
use App\Enums\DashboardModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreScheduleAdjustmentRequest;
use App\Http\Requests\Dashboard\UpdateScheduleAdjustmentRequest;
use App\Http\Resources\ScheduleAdjustmentResource;
use App\Models\ScheduleAdjustment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Schedule adjustments (holidays, special hours) as the dashboard manages
 * them (D-014). The public portal reads the coming ones through
 * PublicOpeningHoursController.
 */
class ScheduleAdjustmentController extends Controller
{
    /**
     * List every adjustment by date.
     */
    public function index(): AnonymousResourceCollection
    {
        return ScheduleAdjustmentResource::collection(ScheduleAdjustment::query()->orderBy('tanggal')->get());
    }

    /**
     * Show one adjustment.
     */
    public function show(ScheduleAdjustment $adjustment): ScheduleAdjustmentResource
    {
        return new ScheduleAdjustmentResource($adjustment);
    }

    /**
     * Add an adjustment. It and its activity log entry are saved together.
     */
    public function store(StoreScheduleAdjustmentRequest $request, RecordActivity $recordActivity): JsonResponse
    {
        $adjustment = DB::transaction(function () use ($request, $recordActivity): ScheduleAdjustment {
            $adjustment = new ScheduleAdjustment($request->validated());
            $adjustment->urutan = (int) (ScheduleAdjustment::query()->max('urutan') ?? -1) + 1;
            $adjustment->save();

            $recordActivity->handle($request->user(), DashboardModule::Jam, 'menambahkan', $adjustment->title());

            return $adjustment;
        });

        return (new ScheduleAdjustmentResource($adjustment))->response()->setStatusCode(201);
    }

    /**
     * Change an adjustment; an unchanged one is not logged.
     */
    public function update(UpdateScheduleAdjustmentRequest $request, ScheduleAdjustment $adjustment, RecordActivity $recordActivity): ScheduleAdjustmentResource
    {
        DB::transaction(function () use ($request, $adjustment, $recordActivity): void {
            $adjustment->fill($request->validated())->save();

            if ($adjustment->wasChanged()) {
                $recordActivity->handle($request->user(), DashboardModule::Jam, 'memperbarui', $adjustment->title());
            }
        });

        return new ScheduleAdjustmentResource($adjustment);
    }
}
