<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\SaveSetting;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateTariffRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

/**
 * The Tarif & Simulasi settings document as the dashboard edits it and the
 * Kalkulator PKB reads it (D-017). The public portal reads it through
 * PublicTariffController.
 */
class TariffController extends Controller
{
    /**
     * Show the tariff; every rate is zero until it is first saved.
     */
    public function show(): JsonResponse
    {
        return response()->json(Setting::tariff());
    }

    /**
     * Replace the tariff.
     */
    public function update(UpdateTariffRequest $request, SaveSetting $saveSetting): JsonResponse
    {
        $setting = $saveSetting->handle($request->user(), Setting::TARIFF, $request->tariff());

        return response()->json($setting->value);
    }
}
