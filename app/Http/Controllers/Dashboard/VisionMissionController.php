<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\SaveSetting;
use App\Actions\SyncUploads;
use App\Enums\UploadTarget;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateVisionMissionRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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
     * Replace the document. The pledge file it now uses is kept; a replaced
     * or removed file is deleted with it (D-028).
     */
    public function update(UpdateVisionMissionRequest $request, SaveSetting $saveSetting, SyncUploads $syncUploads): JsonResponse
    {
        $before = Setting::visionMission();
        $document = $request->visionMission();

        $setting = DB::transaction(function () use ($request, $saveSetting, $syncUploads, $before, $document): Setting {
            $setting = $saveSetting->handle($request->user(), Setting::VISION_MISSION, $document);
            $syncUploads->handle(
                UploadTarget::VisiMisi,
                [$before['maklumat']['berkas']['url'] ?? null],
                [$document['maklumat']['berkas']['url'] ?? null],
            );

            return $setting;
        });

        return response()->json($setting->value);
    }
}
