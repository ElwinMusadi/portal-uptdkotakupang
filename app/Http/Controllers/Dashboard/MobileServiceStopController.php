<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\RecordActivity;
use App\Enums\DashboardModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreMobileServiceStopRequest;
use App\Http\Requests\Dashboard\UpdateMobileServiceStopRequest;
use App\Http\Resources\MobileServiceStopResource;
use App\Models\MobileServiceStop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Samsat Keliling stops as the dashboard manages them (D-013). The public
 * portal reads only active stops through PublicMobileServiceStopController.
 */
class MobileServiceStopController extends Controller
{
    /**
     * List every stop by day and time.
     */
    public function index(): AnonymousResourceCollection
    {
        return MobileServiceStopResource::collection(
            MobileServiceStop::query()->orderBy('hari')->orderBy('mulai')->orderBy('selesai')->orderBy('id')->get()
        );
    }

    /**
     * Show one stop.
     */
    public function show(MobileServiceStop $stop): MobileServiceStopResource
    {
        return new MobileServiceStopResource($stop);
    }

    /**
     * Add a stop. The stop and its activity log entry are saved together.
     */
    public function store(StoreMobileServiceStopRequest $request, RecordActivity $recordActivity): JsonResponse
    {
        $stop = DB::transaction(function () use ($request, $recordActivity): MobileServiceStop {
            $stop = new MobileServiceStop($request->validated());
            $stop->urutan = (int) (MobileServiceStop::query()->max('urutan') ?? -1) + 1;
            $stop->save();

            $recordActivity->handle($request->user(), DashboardModule::Keliling, 'menambahkan', $stop->title());

            return $stop;
        });

        return (new MobileServiceStopResource($stop))->response()->setStatusCode(201);
    }

    /**
     * Change a stop; an unchanged stop is not logged.
     */
    public function update(UpdateMobileServiceStopRequest $request, MobileServiceStop $stop, RecordActivity $recordActivity): MobileServiceStopResource
    {
        DB::transaction(function () use ($request, $stop, $recordActivity): void {
            $stop->fill($request->validated())->save();

            if ($stop->wasChanged()) {
                $recordActivity->handle($request->user(), DashboardModule::Keliling, 'memperbarui', $stop->title());
            }
        });

        return new MobileServiceStopResource($stop);
    }
}
