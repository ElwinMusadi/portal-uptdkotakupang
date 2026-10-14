<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\DeleteEntries;
use App\Enums\DashboardModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\DeleteServiceLocationsRequest;
use App\Models\ServiceLocation;
use Illuminate\Http\Response;

class ServiceLocationDeletionController extends Controller
{
    /**
     * Remove the listed places from the list (ServiceLocationPolicy). Schedule
     * entries that use them keep their place.
     */
    public function store(DeleteServiceLocationsRequest $request, DeleteEntries $deleteEntries): Response
    {
        $deleteEntries->handle(
            $request->user(),
            DashboardModule::Keliling,
            ServiceLocation::class,
            $request->ids(),
            fn (ServiceLocation $location): string => $location->title(),
        );

        return response()->noContent();
    }
}
