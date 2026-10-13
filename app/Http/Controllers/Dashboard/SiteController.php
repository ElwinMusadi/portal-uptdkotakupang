<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\SaveSetting;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateSiteRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

/**
 * The Pengaturan Situs document as the dashboard edits it (D-026). The
 * public portal reads its footer and announcement through
 * PublicSiteController.
 */
class SiteController extends Controller
{
    /**
     * Show the document; until it is first saved, the identity and
     * announcement written in the portal's markup.
     */
    public function show(): JsonResponse
    {
        return response()->json(Setting::site());
    }

    /**
     * Replace the document.
     */
    public function update(UpdateSiteRequest $request, SaveSetting $saveSetting): JsonResponse
    {
        $setting = $saveSetting->handle($request->user(), Setting::SITE, $request->site());

        return response()->json($setting->value);
    }
}
