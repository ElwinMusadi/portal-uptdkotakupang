<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\SaveSetting;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateSchedulePageRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

/**
 * The Jadwal page's texts as Layanan Luar Kantor edits them (D-080). The
 * public portal reads them through PublicSchedulePageController.
 */
class SchedulePageController extends Controller
{
    /**
     * Show the document; until it is first saved, the text written in the
     * page's markup.
     */
    public function show(): JsonResponse
    {
        return response()->json(Setting::schedulePage());
    }

    /**
     * Replace the document; a change is logged as "memperbarui Layanan Luar
     * Kantor".
     */
    public function update(UpdateSchedulePageRequest $request, SaveSetting $saveSetting): JsonResponse
    {
        $setting = $saveSetting->handle($request->user(), Setting::SCHEDULE_PAGE, $request->schedulePage());

        return response()->json($setting->value);
    }
}
