<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\RecordActivity;
use App\Actions\UpdateWorkflowEntry;
use App\Enums\DashboardModule;
use App\Enums\WorkflowStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreServiceRequest;
use App\Http\Requests\Dashboard\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Services and their requirements as the dashboard manages them (D-022).
 * The public portal reads only published services through
 * PublicServiceController.
 */
class ServiceController extends Controller
{
    /**
     * List every service in display order.
     */
    public function index(): AnonymousResourceCollection
    {
        return ServiceResource::collection(Service::query()->orderBy('urutan')->orderBy('id')->get());
    }

    /**
     * Show one service.
     */
    public function show(Service $service): ServiceResource
    {
        return new ServiceResource($service);
    }

    /**
     * Add a service as a draft at the end of the list, prepared by the
     * signed-in employee. The service and its activity log entry are saved
     * together.
     */
    public function store(StoreServiceRequest $request, RecordActivity $recordActivity): JsonResponse
    {
        $service = DB::transaction(function () use ($request, $recordActivity): Service {
            $service = new Service($request->content());
            $service->forceFill([
                'urutan' => (int) (Service::query()->max('urutan') ?? -1) + 1,
                'catatan_revisi' => '',
                'terbit_pada' => null,
                'riwayat' => [],
            ]);
            $service->moveTo(WorkflowStatus::Draft, $request->user()->name, 'menyiapkan draf');
            $service->save();
            $recordActivity->handle($request->user(), DashboardModule::Layanan, 'menambahkan', $service->nama);

            return $service;
        });

        return (new ServiceResource($service))->response()->setStatusCode(201);
    }

    /**
     * Change a service, take a workflow step, or both (UpdateWorkflowEntry).
     */
    public function update(UpdateServiceRequest $request, Service $service, UpdateWorkflowEntry $updateEntry): ServiceResource
    {
        return new ServiceResource($updateEntry->handle(
            $request->user(),
            $service,
            DashboardModule::Layanan,
            'nama',
            $request->content(),
            $request->nextStatus(),
            $request->revisionNote(),
        ));
    }
}
