<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\RecordActivity;
use App\Enums\DashboardModule;
use App\Enums\QueueTicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\SaveQueueCounterRequest;
use App\Http\Resources\QueueCounterResource;
use App\Models\QueueCounter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * The counters of Antrean Online (D-051): listed for the counter page,
 * arranged by administrators. Changes are logged.
 */
class QueueCounterController extends Controller
{
    /**
     * Every counter, including those not in use.
     */
    public function index(): AnonymousResourceCollection
    {
        return QueueCounterResource::collection(QueueCounter::query()->ordered()->get());
    }

    /**
     * Add a counter after the others.
     */
    public function store(SaveQueueCounterRequest $request, RecordActivity $recordActivity): JsonResponse
    {
        $counter = DB::transaction(function () use ($request, $recordActivity): QueueCounter {
            $counter = QueueCounter::query()->create([...$request->counter(), 'urutan' => (int) QueueCounter::query()->max('urutan') + 1]);
            $recordActivity->handle($request->user(), DashboardModule::Antrean, 'menambahkan', "Loket antrean {$counter->nama}");

            return $counter;
        });

        return QueueCounterResource::make($counter)->response()->setStatusCode(201);
    }

    /**
     * Change a counter. Saving the same values logs nothing.
     */
    public function update(SaveQueueCounterRequest $request, QueueCounter $queueCounter, RecordActivity $recordActivity): QueueCounterResource
    {
        $queueCounter->fill($request->counter());

        if ($queueCounter->isDirty()) {
            DB::transaction(function () use ($queueCounter, $request, $recordActivity): void {
                $queueCounter->save();
                $recordActivity->handle($request->user(), DashboardModule::Antrean, 'memperbarui', "Loket antrean {$queueCounter->nama}");
            });
        }

        return QueueCounterResource::make($queueCounter);
    }

    /**
     * Remove a counter. A number it is calling waits among the put-aside
     * numbers of its stage, to be called elsewhere.
     */
    public function destroy(Request $request, QueueCounter $queueCounter, RecordActivity $recordActivity): Response
    {
        DB::transaction(function () use ($request, $queueCounter, $recordActivity): void {
            $queueCounter->tickets()->where('status', QueueTicketStatus::Dipanggil)->update(['status' => QueueTicketStatus::Ditunda]);
            $queueCounter->delete();
            $recordActivity->handle($request->user(), DashboardModule::Antrean, 'menghapus', "Loket antrean {$queueCounter->nama}");
        });

        return response()->noContent();
    }
}
