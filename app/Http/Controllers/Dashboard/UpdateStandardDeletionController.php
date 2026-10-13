<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\DeleteEntries;
use App\Enums\DashboardModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\DeleteUpdateStandardsRequest;
use App\Models\UpdateStandard;
use Illuminate\Http\Response;

class UpdateStandardDeletionController extends Controller
{
    /**
     * Delete the listed standards (UpdateStandardPolicy).
     */
    public function store(DeleteUpdateStandardsRequest $request, DeleteEntries $deleteEntries): Response
    {
        $deleteEntries->handle(
            $request->user(),
            DashboardModule::Pemutakhiran,
            UpdateStandard::class,
            $request->ids(),
            fn (UpdateStandard $standard): string => $standard->konten,
        );

        return response()->noContent();
    }
}
