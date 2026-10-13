<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\SaveSetting;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateStatisticsRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

/**
 * The Statistik Layanan settings document as the dashboard edits it
 * (D-018). The public portal reads the verified figures through
 * PublicStatisticsController.
 */
class StatisticsController extends Controller
{
    /**
     * Show the statistics; every figure is zero until they are first saved.
     */
    public function show(): JsonResponse
    {
        return response()->json(Setting::statistics());
    }

    /**
     * Replace the statistics.
     */
    public function update(UpdateStatisticsRequest $request, SaveSetting $saveSetting): JsonResponse
    {
        $setting = $saveSetting->handle($request->user(), Setting::STATISTICS, $request->statistics());

        return response()->json($setting->value);
    }
}
