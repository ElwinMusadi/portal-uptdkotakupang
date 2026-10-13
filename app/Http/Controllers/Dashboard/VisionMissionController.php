<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\SaveSetting;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateVisionMissionRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

/**
 * The Visi & Misi settings document as the dashboard edits it (D-020). The
 * public portal reads it through PublicVisionMissionController.
 */
class VisionMissionController extends Controller
{
    /**
     * Show the document; until it is first saved, the official text the
     * portal showed before.
     */
    public function show(): JsonResponse
    {
        return response()->json(Setting::visionMission());
    }

    /**
     * Replace the document.
     */
    public function update(UpdateVisionMissionRequest $request, SaveSetting $saveSetting): JsonResponse
    {
        $setting = $saveSetting->handle($request->user(), Setting::VISION_MISSION, $request->visionMission());

        return response()->json($setting->value);
    }
}
