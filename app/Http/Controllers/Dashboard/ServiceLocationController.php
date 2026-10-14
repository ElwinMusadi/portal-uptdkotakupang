<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\RecordActivity;
use App\Enums\DashboardModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreServiceLocationRequest;
use App\Http\Requests\Dashboard\UpdateServiceLocationRequest;
use App\Http\Resources\ServiceLocationResource;
use App\Models\ServiceLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * The list of places picked when service outside the office is scheduled
 * (D-044), managed by the Layanan Luar Kantor module.
 */
class ServiceLocationController extends Controller
{
    /**
     * List every place by name.
     */
    public function index(): AnonymousResourceCollection
    {
        return ServiceLocationResource::collection(ServiceLocation::query()->orderBy('nama')->orderBy('id')->get());
    }

    /**
     * Show one place.
     */
    public function show(ServiceLocation $location): ServiceLocationResource
    {
        return new ServiceLocationResource($location);
    }

    /**
     * Add a place. The place and its activity log entry are saved together.
     */
    public function store(StoreServiceLocationRequest $request, RecordActivity $recordActivity): JsonResponse
    {
        $location = DB::transaction(function () use ($request, $recordActivity): ServiceLocation {
            $location = new ServiceLocation($request->validated());
            $location->urutan = (int) (ServiceLocation::query()->max('urutan') ?? -1) + 1;
            $location->save();
            $recordActivity->handle($request->user(), DashboardModule::Keliling, 'menambahkan', $location->title());

            return $location;
        });

        return (new ServiceLocationResource($location))->response()->setStatusCode(201);
    }

    /**
     * Change a place; an unchanged place is not logged.
     */
    public function update(UpdateServiceLocationRequest $request, ServiceLocation $location, RecordActivity $recordActivity): ServiceLocationResource
    {
        DB::transaction(function () use ($request, $location, $recordActivity): void {
            $location->fill($request->validated())->save();

            if ($location->wasChanged()) {
                $recordActivity->handle($request->user(), DashboardModule::Keliling, 'memperbarui', $location->title());
            }
        });

        return new ServiceLocationResource($location);
    }
}
