<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\DeleteEntries;
use App\Actions\SyncUploads;
use App\Enums\DashboardModule;
use App\Enums\UploadTarget;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\DeleteOrganizationUnitsRequest;
use App\Models\OrganizationUnit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;

class OrganizationUnitDeletionController extends Controller
{
    /**
     * Delete the listed units and their officials' photos. Units that
     * others report to are refused (DeleteOrganizationUnitsRequest).
     */
    public function store(DeleteOrganizationUnitsRequest $request, DeleteEntries $deleteEntries, SyncUploads $syncUploads): Response
    {
        $deleteEntries->handle(
            $request->user(),
            DashboardModule::Unit,
            OrganizationUnit::class,
            $request->ids(),
            fn (OrganizationUnit $unit): string => $unit->nama,
            fn (Collection $units) => $syncUploads->handle(UploadTarget::Unit, $units->pluck('foto')->all(), []),
        );

        return response()->noContent();
    }
}
