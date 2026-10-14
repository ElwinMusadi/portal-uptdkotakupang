<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\CopyMobileServiceStops;
use App\Actions\RecordActivity;
use App\Enums\DashboardModule;
use App\Enums\ServicePointKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\CopyMobileServiceStopsRequest;
use App\Http\Resources\MobileServiceStopResource;
use App\Models\MobileServiceStop;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MobileServiceStopCopyController extends Controller
{
    /**
     * Copy a week's Samsat Keliling stops to the same weekdays of the
     * following week (D-043), switched off so staff can change units and
     * places before they are published. The copies and one activity log
     * entry are saved together.
     */
    public function __invoke(CopyMobileServiceStopsRequest $request, CopyMobileServiceStops $copyStops, RecordActivity $recordActivity): AnonymousResourceCollection
    {
        $week = $request->week();

        $copies = DB::transaction(function () use ($request, $copyStops, $recordActivity, $week): Collection {
            $stops = MobileServiceStop::query()
                ->where('jenis', ServicePointKind::Keliling)
                ->whereDate('tanggal_mulai', '>=', $week->toDateString())
                ->whereDate('tanggal_mulai', '<=', $week->copy()->addDays(6)->toDateString())
                ->orderBy('tanggal_mulai')
                ->orderBy('mulai')
                ->orderBy('id')
                ->get();

            $copies = $copyStops->handle(
                $stops->map(fn (MobileServiceStop $stop): array => [$stop, $stop->tanggal_mulai->copy()->addWeek()->toDateString()]),
                active: false,
            );

            if ($copies->isNotEmpty()) {
                $nextWeek = $week->copy()->addWeek()->toDateString();
                $recordActivity->handle($request->user(), DashboardModule::Keliling, 'menyalin', "{$copies->count()} jadwal Samsat Keliling ke minggu {$nextWeek}");
            }

            return $copies;
        });

        return MobileServiceStopResource::collection($copies);
    }
}
