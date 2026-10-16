<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\DeleteEntries;
use App\Actions\SyncUploads;
use App\Enums\DashboardModule;
use App\Enums\UploadTarget;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\DeleteAgencyAppsRequest;
use App\Models\AgencyApp;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;

class AgencyAppDeletionController extends Controller
{
    /**
     * Delete the listed apps and their uploaded logos. Logos that came with
     * the first list stay on the site.
     */
    public function store(DeleteAgencyAppsRequest $request, DeleteEntries $deleteEntries, SyncUploads $syncUploads): Response
    {
        $deleteEntries->handle(
            $request->user(),
            DashboardModule::Aplikasi,
            AgencyApp::class,
            $request->ids(),
            fn (AgencyApp $app): string => $app->nama,
            fn (Collection $apps) => $syncUploads->handle(UploadTarget::Aplikasi, $apps->pluck('logo')->all(), []),
        );

        return response()->noContent();
    }
}
