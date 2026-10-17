<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\SaveTheme;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateThemeRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

/**
 * The portal and dashboard themes as Pengaturan › Tampilan edits them
 * (D-057). /tema-portal.css and /tema-dashboard.css are built from them.
 */
class ThemeController extends Controller
{
    /**
     * Show the document; the built-in PINTU look until it is first saved.
     */
    public function show(): JsonResponse
    {
        return response()->json(Setting::theme());
    }

    /**
     * Replace the document; a changed theme is kept in the history.
     */
    public function update(UpdateThemeRequest $request, SaveTheme $saveTheme): JsonResponse
    {
        $setting = $saveTheme->handle($request->user(), $request->theme());

        return response()->json($setting->value);
    }
}
