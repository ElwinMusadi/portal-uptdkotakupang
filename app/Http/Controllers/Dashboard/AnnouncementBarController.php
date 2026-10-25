<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\SaveSetting;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateAnnouncementBarRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

/**
 * The announcement bar as Berita & Pengumuman edits it (D-082). The public
 * portal reads it through PublicAnnouncementBarController.
 */
class AnnouncementBarController extends Controller
{
    /**
     * Show the bar; until it is first saved, the one in the Informasi page's
     * markup.
     */
    public function show(): JsonResponse
    {
        return response()->json(Setting::announcementBar());
    }

    /**
     * Replace the bar; a change is logged as "memperbarui Berita &
     * Pengumuman".
     */
    public function update(UpdateAnnouncementBarRequest $request, SaveSetting $saveSetting): JsonResponse
    {
        $setting = $saveSetting->handle($request->user(), Setting::ANNOUNCEMENT_BAR, $request->announcementBar());

        return response()->json($setting->value);
    }
}
