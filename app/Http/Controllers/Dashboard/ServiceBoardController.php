<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\SaveSetting;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateServiceBoardRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

/**
 * The Papan Layanan settings document as the dashboard edits it (D-015).
 * The public portal reads it through PublicServiceBoardController.
 */
class ServiceBoardController extends Controller
{
    /**
     * Show the service board; it is empty until first saved.
     */
    public function show(): JsonResponse
    {
        return response()->json(Setting::serviceBoard());
    }

    /**
     * Replace the service board. Saving the same content again keeps the
     * previous "diperbarui" time.
     */
    public function update(UpdateServiceBoardRequest $request, SaveSetting $saveSetting): JsonResponse
    {
        $setting = $saveSetting->handle($request->user(), Setting::SERVICE_BOARD, $request->serviceBoard(), ['diperbarui']);

        return response()->json($setting->value);
    }
}
