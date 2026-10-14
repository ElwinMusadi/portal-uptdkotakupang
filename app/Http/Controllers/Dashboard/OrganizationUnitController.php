<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\RecordActivity;
use App\Actions\SyncUploads;
use App\Enums\DashboardModule;
use App\Enums\UploadTarget;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreOrganizationUnitRequest;
use App\Http\Requests\Dashboard\UpdateOrganizationUnitRequest;
use App\Http\Resources\OrganizationUnitResource;
use App\Models\OrganizationUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Struktur Organisasi as the dashboard manages it (D-033). Changes reach
 * the portal's chart (PublicOrganizationController) when saved.
 */
class OrganizationUnitController extends Controller
{
    public function __construct(private RecordActivity $recordActivity, private SyncUploads $syncUploads) {}

    /**
     * List every unit in display order.
     */
    public function index(): AnonymousResourceCollection
    {
        return OrganizationUnitResource::collection(OrganizationUnit::query()->orderBy('urutan')->orderBy('id')->get());
    }

    /**
     * Show one unit.
     */
    public function show(OrganizationUnit $unit): OrganizationUnitResource
    {
        return new OrganizationUnitResource($unit);
    }

    /**
     * Add a unit at the end of the list. The unit, its activity log entry
     * and its photo's use are saved together.
     */
    public function store(StoreOrganizationUnitRequest $request): JsonResponse
    {
        $unit = DB::transaction(function () use ($request): OrganizationUnit {
            $unit = new OrganizationUnit($request->content());
            $unit->forceFill(['urutan' => (int) (OrganizationUnit::query()->max('urutan') ?? -1) + 1]);
            $unit->save();
            $this->recordActivity->handle($request->user(), DashboardModule::Unit, 'menambahkan', $unit->nama);
            $this->syncUploads->handle(UploadTarget::Unit, [], [$unit->foto]);

            return $unit;
        });

        return (new OrganizationUnitResource($unit))->response()->setStatusCode(201);
    }

    /**
     * Change a unit; an unchanged unit is not logged. A replaced or removed
     * photo is deleted with the change.
     */
    public function update(UpdateOrganizationUnitRequest $request, OrganizationUnit $unit): OrganizationUnitResource
    {
        $photo = $unit->foto;

        DB::transaction(function () use ($request, $unit, $photo): void {
            $unit->fill($request->content());

            if ($unit->isDirty()) {
                $unit->save();
                $this->recordActivity->handle($request->user(), DashboardModule::Unit, 'memperbarui', $unit->nama);
            }

            $this->syncUploads->handle(UploadTarget::Unit, [$photo], [$unit->foto]);
        });

        return new OrganizationUnitResource($unit);
    }
}
