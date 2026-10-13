<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\DeleteEntries;
use App\Enums\DashboardModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\DeleteMobileServiceStopsRequest;
use App\Models\MobileServiceStop;
use Illuminate\Http\Response;

class MobileServiceStopDeletionController extends Controller
{
    /**
     * Delete the listed stops (MobileServiceStopPolicy).
     */
    public function store(DeleteMobileServiceStopsRequest $request, DeleteEntries $deleteEntries): Response
    {
        $deleteEntries->handle(
            $request->user(),
            DashboardModule::Keliling,
            MobileServiceStop::class,
            $request->ids(),
            fn (MobileServiceStop $stop): string => $stop->title(),
        );

        return response()->noContent();
    }
}
