<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\SaveOpeningHours;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateOpeningHoursRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

/**
 * The Jam Pelayanan settings document as the dashboard edits it (D-014).
 * The public portal reads it through PublicOpeningHoursController.
 */
class OpeningHoursController extends Controller
{
    /**
     * Show the opening hours; every day is closed until they are first saved.
     */
    public function show(): JsonResponse
    {
        return response()->json(Setting::openingHours());
    }

    /**
     * Replace the opening hours.
     */
    public function update(UpdateOpeningHoursRequest $request, SaveOpeningHours $saveOpeningHours): JsonResponse
    {
        $setting = $saveOpeningHours->handle($request->user(), $request->openingHours());

        return response()->json($setting->value);
    }
}
