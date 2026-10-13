<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\RecordActivity;
use App\Enums\DashboardModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreUpdateStandardRequest;
use App\Http\Requests\Dashboard\UpdateUpdateStandardRequest;
use App\Http\Resources\UpdateStandardResource;
use App\Models\UpdateStandard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Jadwal Pemutakhiran's standards as the dashboard manages them (D-025).
 * They are internal; the portal does not read them.
 */
class UpdateStandardController extends Controller
{
    /**
     * List the standards the user follows, in display order.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return UpdateStandardResource::collection(
            UpdateStandard::query()->followedBy($request->user())->orderBy('urutan')->orderBy('id')->get()
        );
    }

    /**
     * Show one standard.
     */
    public function show(UpdateStandard $standard): UpdateStandardResource
    {
        return new UpdateStandardResource($standard);
    }

    /**
     * Add a standard at the end of the list. The standard and its activity
     * log entry are saved together.
     */
    public function store(StoreUpdateStandardRequest $request, RecordActivity $recordActivity): JsonResponse
    {
        $standard = DB::transaction(function () use ($request, $recordActivity): UpdateStandard {
            $standard = new UpdateStandard($request->content());
            $standard->urutan = (int) (UpdateStandard::query()->max('urutan') ?? -1) + 1;
            $standard->save();

            $recordActivity->handle($request->user(), DashboardModule::Pemutakhiran, 'menambahkan', $standard->konten);

            return $standard;
        });

        return (new UpdateStandardResource($standard))->response()->setStatusCode(201);
    }

    /**
     * Change a standard; an unchanged standard is not logged.
     */
    public function update(UpdateUpdateStandardRequest $request, UpdateStandard $standard, RecordActivity $recordActivity): UpdateStandardResource
    {
        DB::transaction(function () use ($request, $standard, $recordActivity): void {
            $standard->fill($request->content())->save();

            if ($standard->wasChanged()) {
                $recordActivity->handle($request->user(), DashboardModule::Pemutakhiran, 'memperbarui', $standard->konten);
            }
        });

        return new UpdateStandardResource($standard);
    }
}
