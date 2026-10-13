<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\SaveSetting;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateHomepageRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

/**
 * The Beranda settings document as the dashboard edits it (D-023). The
 * public portal reads it through PublicHomepageController.
 */
class HomepageController extends Controller
{
    /**
     * Show the document; until it is first saved, the text written in the
     * homepage's markup.
     */
    public function show(): JsonResponse
    {
        return response()->json(Setting::homepage());
    }

    /**
     * Replace the document.
     */
    public function update(UpdateHomepageRequest $request, SaveSetting $saveSetting): JsonResponse
    {
        $setting = $saveSetting->handle($request->user(), Setting::HOMEPAGE, $request->homepage());

        return response()->json($setting->value);
    }
}
