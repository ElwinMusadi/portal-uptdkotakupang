<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\CopyMobileServiceStops;
use App\Actions\RecordActivity;
use App\Enums\DashboardModule;
use App\Enums\ServicePointKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\CopyMobileServiceStopsToDaysRequest;
use App\Http\Resources\MobileServiceStopResource;
use App\Models\MobileServiceStop;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MobileServiceStopDayCopyController extends Controller
{
    /**
     * Copy every Samsat Keliling stop of one day to the chosen days (D-044),
     * each copy shown on the portal when its stop is. The copies and one
     * activity log entry are saved together.
     */
    public function __invoke(CopyMobileServiceStopsToDaysRequest $request, CopyMobileServiceStops $copyStops, RecordActivity $recordActivity): AnonymousResourceCollection
    {
        $day = $request->validated('dari');

        $copies = DB::transaction(function () use ($request, $copyStops, $recordActivity, $day): Collection {
            $stops = MobileServiceStop::query()
                ->where('jenis', ServicePointKind::Keliling)
                ->whereDate('tanggal_mulai', $day)
                ->orderBy('mulai')
                ->orderBy('unit')
                ->orderBy('id')
                ->get();

            $plan = [];
            foreach ($request->days() as $date) {
                foreach ($stops as $stop) {
                    $plan[] = [$stop, $date];
                }
            }

            $copies = $copyStops->handle($plan, active: null);

            if ($copies->isNotEmpty()) {
                $recordActivity->handle($request->user(), DashboardModule::Keliling, 'menyalin', "{$copies->count()} jadwal Samsat Keliling dari {$day}");
            }

            return $copies;
        });

        return MobileServiceStopResource::collection($copies);
    }
}
