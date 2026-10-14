<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\SaveSetting;
use App\Actions\SyncUploads;
use App\Enums\UploadTarget;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateHomepageRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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
     * Replace the document. Partner logos it now uses are kept; replaced or
     * removed logos are deleted with it (D-037).
     */
    public function update(UpdateHomepageRequest $request, SaveSetting $saveSetting, SyncUploads $syncUploads): JsonResponse
    {
        $before = Setting::homepage();
        $homepage = $request->homepage();

        $setting = DB::transaction(function () use ($request, $saveSetting, $syncUploads, $before, $homepage): Setting {
            $setting = $saveSetting->handle($request->user(), Setting::HOMEPAGE, $homepage);
            $syncUploads->handle(
                UploadTarget::Beranda,
                array_column($before['mitra'], 'logo'),
                array_column($homepage['mitra'], 'logo'),
            );

            return $setting;
        });

        return response()->json($setting->value);
    }
}
