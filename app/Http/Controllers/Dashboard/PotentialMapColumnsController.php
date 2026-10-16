<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\SaveSetting;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdatePotentialMapColumnsRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

/**
 * The columns of the Pemetaan PKB tables (D-052): read by everyone with the
 * module, chosen by an Administrator. The portal's kecamatan and kelurahan
 * tables follow the same choice.
 */
class PotentialMapColumnsController extends Controller
{
    /**
     * Show the columns of both tables.
     */
    public function show(): JsonResponse
    {
        return response()->json(Setting::potentialMapColumns());
    }

    /**
     * Choose the columns of both tables; logged as "memperbarui Pemetaan PKB"
     * when they change.
     */
    public function update(UpdatePotentialMapColumnsRequest $request, SaveSetting $saveSetting): JsonResponse
    {
        $setting = $saveSetting->handle($request->user(), Setting::POTENTIAL_MAP, $request->columns());

        return response()->json($setting->value);
    }
}
