<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\SaveSetting;
use App\Actions\SyncUploads;
use App\Enums\UploadTarget;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateProfileRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * The Tentang UPTD settings document as the dashboard edits it (D-021). The
 * public portal reads it through PublicProfileController.
 */
class ProfileController extends Controller
{
    /**
     * Show the document; until it is first saved, the text the portal showed
     * before.
     */
    public function show(): JsonResponse
    {
        return response()->json(Setting::profile());
    }

    /**
     * Replace the document. Photos it now uses are kept; replaced or removed
     * photos are deleted with it (D-028).
     */
    public function update(UpdateProfileRequest $request, SaveSetting $saveSetting, SyncUploads $syncUploads): JsonResponse
    {
        $before = Setting::profile();
        $profile = $request->profile();

        $setting = DB::transaction(function () use ($request, $saveSetting, $syncUploads, $before, $profile): Setting {
            $setting = $saveSetting->handle($request->user(), Setting::PROFILE, $profile);
            $syncUploads->handle(
                UploadTarget::Profil,
                [$before['foto'] ?? null, $before['sambutan']['foto'] ?? null],
                [$profile['foto'], $profile['sambutan']['foto']],
            );

            return $setting;
        });

        return response()->json($setting->value);
    }
}
