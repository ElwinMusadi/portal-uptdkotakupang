<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\RecordActivity;
use App\Enums\DashboardModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateQueueServiceRequest;
use App\Http\Resources\QueueServiceResource;
use App\Models\QueueService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * The services of Antrean Online (D-051): listed for the information desk,
 * changed by administrators.
 */
class QueueServiceController extends Controller
{
    /**
     * Every service in desk order, including inactive ones.
     */
    public function index(): AnonymousResourceCollection
    {
        return QueueServiceResource::collection(QueueService::query()->ordered()->get());
    }

    /**
     * Change a service's name, scope, stages or whether it is active. Tickets
     * already printed keep the stages they were printed with. Saving the same
     * values logs nothing.
     */
    public function update(UpdateQueueServiceRequest $request, QueueService $queueService, RecordActivity $recordActivity): QueueServiceResource
    {
        $queueService->fill($request->service());

        if ($queueService->isDirty()) {
            DB::transaction(function () use ($queueService, $request, $recordActivity): void {
                $queueService->save();
                $recordActivity->handle($request->user(), DashboardModule::Antrean, 'memperbarui', "Layanan antrean {$queueService->kode} ({$queueService->nama})");
            });
        }

        return QueueServiceResource::make($queueService);
    }
}
