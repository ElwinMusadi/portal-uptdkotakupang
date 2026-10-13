<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\DeleteEntries;
use App\Enums\DashboardModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\DeleteServicesRequest;
use App\Models\Service;
use Illuminate\Http\Response;

class ServiceDeletionController extends Controller
{
    /**
     * Delete the listed services; only administrators may (ServicePolicy).
     */
    public function store(DeleteServicesRequest $request, DeleteEntries $deleteEntries): Response
    {
        $deleteEntries->handle($request->user(), DashboardModule::Layanan, Service::class, $request->ids(), fn (Service $service): string => $service->nama);

        return response()->noContent();
    }
}
