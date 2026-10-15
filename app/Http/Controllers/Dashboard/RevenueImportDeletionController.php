<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\DeleteEntries;
use App\Enums\DashboardModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\DeleteRevenueImportsRequest;
use App\Models\RevenueImport;
use Illuminate\Http\Response;

class RevenueImportDeletionController extends Controller
{
    /**
     * Remove the listed imports; the database removes their transactions with
     * them (RevenueImportPolicy).
     */
    public function store(DeleteRevenueImportsRequest $request, DeleteEntries $deleteEntries): Response
    {
        $deleteEntries->handle(
            $request->user(),
            DashboardModule::Penerimaan,
            RevenueImport::class,
            $request->ids(),
            fn (RevenueImport $import): string => $import->title(),
        );

        return response()->noContent();
    }
}
