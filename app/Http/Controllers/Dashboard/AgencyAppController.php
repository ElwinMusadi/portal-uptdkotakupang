<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\RecordActivity;
use App\Actions\SyncUploads;
use App\Enums\DashboardModule;
use App\Enums\UploadTarget;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreAgencyAppRequest;
use App\Http\Requests\Dashboard\UpdateAgencyAppRequest;
use App\Http\Resources\AgencyAppResource;
use App\Models\AgencyApp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Aplikasi & Situs as the dashboard manages it (D-054). Changes reach the
 * portal's page (PublicAgencyAppController) when saved.
 */
class AgencyAppController extends Controller
{
    public function __construct(private RecordActivity $recordActivity, private SyncUploads $syncUploads) {}

    /**
     * List every app in display order.
     */
    public function index(): AnonymousResourceCollection
    {
        return AgencyAppResource::collection(AgencyApp::query()->orderBy('urutan')->orderBy('id')->get());
    }

    /**
     * Show one app.
     */
    public function show(AgencyApp $agencyApp): AgencyAppResource
    {
        return new AgencyAppResource($agencyApp);
    }

    /**
     * Add an app at the end of the list. The app, its activity log entry and
     * its logo's use are saved together.
     */
    public function store(StoreAgencyAppRequest $request): JsonResponse
    {
        $app = DB::transaction(function () use ($request): AgencyApp {
            $app = new AgencyApp($request->content());
            $app->forceFill(['urutan' => (int) (AgencyApp::query()->max('urutan') ?? -1) + 1]);
            $app->save();
            $this->recordActivity->handle($request->user(), DashboardModule::Aplikasi, 'menambahkan', $app->nama);
            $this->syncUploads->handle(UploadTarget::Aplikasi, [], [$app->logo]);

            return $app;
        });

        return (new AgencyAppResource($app))->response()->setStatusCode(201);
    }

    /**
     * Change an app; an unchanged app is not logged. A replaced or removed
     * uploaded logo is deleted with the change.
     */
    public function update(UpdateAgencyAppRequest $request, AgencyApp $agencyApp): AgencyAppResource
    {
        $logo = $agencyApp->logo;

        DB::transaction(function () use ($request, $agencyApp, $logo): void {
            $agencyApp->fill($request->content());

            if ($agencyApp->isDirty()) {
                $agencyApp->save();
                $this->recordActivity->handle($request->user(), DashboardModule::Aplikasi, 'memperbarui', $agencyApp->nama);
            }

            $this->syncUploads->handle(UploadTarget::Aplikasi, [$logo], [$agencyApp->logo]);
        });

        return new AgencyAppResource($agencyApp);
    }
}
