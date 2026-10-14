<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreFieldActivityRequest;
use App\Http\Requests\Dashboard\UpdateFieldActivityRequest;
use App\Http\Resources\FieldActivityResource;
use App\Models\FieldActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Field activities as administrators manage them in the dashboard, with
 * the officers' clock-ins (D-034).
 */
class FieldActivityController extends Controller
{
    /**
     * List every activity, newest date first, with its officers and how
     * many have clocked in.
     */
    public function index(): AnonymousResourceCollection
    {
        $activities = FieldActivity::query()
            ->with('officers')
            ->withCount('attendances')
            ->orderByDesc('tanggal')
            ->orderBy('nama')
            ->get();

        return FieldActivityResource::collection($activities);
    }

    /**
     * Show one activity with its officers and their clock-ins.
     */
    public function show(FieldActivity $activity): FieldActivityResource
    {
        return new FieldActivityResource($activity->load(['officers', 'attendances.user'])->loadCount('attendances'));
    }

    /**
     * Add an activity and assign its officers.
     */
    public function store(StoreFieldActivityRequest $request): JsonResponse
    {
        $activity = DB::transaction(function () use ($request): FieldActivity {
            $activity = FieldActivity::query()->create($request->content());
            $activity->officers()->sync($request->officers());

            return $activity;
        });

        return (new FieldActivityResource($activity->load('officers')->loadCount('attendances')))->response()->setStatusCode(201);
    }

    /**
     * Change an activity, its officers, or both. Clock-ins already recorded
     * keep the distance measured at the time.
     */
    public function update(UpdateFieldActivityRequest $request, FieldActivity $activity): FieldActivityResource
    {
        DB::transaction(function () use ($request, $activity): void {
            $activity->update($request->content());

            if ($request->officers() !== null) {
                $activity->officers()->sync($request->officers());
            }
        });

        return new FieldActivityResource($activity->load('officers')->loadCount('attendances'));
    }
}
