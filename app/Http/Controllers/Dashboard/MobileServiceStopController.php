<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\RecordActivity;
use App\Actions\RememberServiceLocation;
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
 * Service outside the office as the dashboard manages it (D-013, D-043):
 * Samsat Keliling stops, fixed outlets and temporary counters. The public
 * portal reads only active entries through PublicMobileServiceStopController.
 * A place typed into an entry joins the list of places (D-044).
 */
class MobileServiceStopController extends Controller
{
    /**
     * List every entry by kind, first date and time.
     */
    public function index(): AnonymousResourceCollection
    {
        return MobileServiceStopResource::collection(
            MobileServiceStop::query()->orderBy('jenis')->orderBy('tanggal_mulai')->orderBy('mulai')->orderBy('selesai')->orderBy('id')->get()
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
     * Add a stop. The stop, its place when new to the list, and their
     * activity log entries are saved together.
     */
    public function store(StoreMobileServiceStopRequest $request, RecordActivity $recordActivity, RememberServiceLocation $rememberLocation): JsonResponse
    {
        $stop = DB::transaction(function () use ($request, $recordActivity, $rememberLocation): MobileServiceStop {
            $stop = new MobileServiceStop($request->stop());
            $stop->urutan = (int) (MobileServiceStop::query()->max('urutan') ?? -1) + 1;
            $stop->save();

            $recordActivity->handle($request->user(), DashboardModule::Keliling, 'menambahkan', $stop->title());
            $rememberLocation->handle($request->user(), $stop->lokasi, $stop->kecamatan);

            return $stop;
        });

        return (new MobileServiceStopResource($stop))->response()->setStatusCode(201);
    }

    /**
     * Change a stop; an unchanged stop is not logged. A changed place joins
     * the list of places when new to it.
     */
    public function update(UpdateMobileServiceStopRequest $request, MobileServiceStop $stop, RecordActivity $recordActivity, RememberServiceLocation $rememberLocation): MobileServiceStopResource
    {
        DB::transaction(function () use ($request, $stop, $recordActivity, $rememberLocation): void {
            $stop->fill($request->stop())->save();

            if ($stop->wasChanged()) {
                $recordActivity->handle($request->user(), DashboardModule::Keliling, 'memperbarui', $stop->title());
            }

            if ($stop->wasChanged('lokasi')) {
                $rememberLocation->handle($request->user(), $stop->lokasi, $stop->kecamatan);
            }
        });

        return new MobileServiceStopResource($stop);
    }
}
