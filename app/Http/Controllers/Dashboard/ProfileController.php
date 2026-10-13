<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\SaveSetting;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateProfileRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

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
     * Replace the document.
     */
    public function update(UpdateProfileRequest $request, SaveSetting $saveSetting): JsonResponse
    {
        $setting = $saveSetting->handle($request->user(), Setting::PROFILE, $request->profile());

        return response()->json($setting->value);
    }
}
