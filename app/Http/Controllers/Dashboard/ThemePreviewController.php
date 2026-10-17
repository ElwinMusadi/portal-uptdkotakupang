<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\BuildThemeStylesheet;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateThemeRequest;
use App\Support\ThemeVariables;
use Illuminate\Http\JsonResponse;

class ThemePreviewController extends Controller
{
    /**
     * Show an unsaved theme as it would be applied (D-057), so Pengaturan ›
     * Tampilan previews it while it is edited: the portal's themable tokens
     * and font rules for the portal sample (the portal's variable names are
     * also the dashboard's, so its stylesheet cannot be applied there), the
     * dashboard's accent as adjusted and its stylesheet, and the contrast of
     * the colour pairs that carry text. Nothing is saved.
     */
    public function __invoke(UpdateThemeRequest $request, BuildThemeStylesheet $buildStylesheet): JsonResponse
    {
        ['portal' => $portal, 'dashboard' => $dashboard] = $request->theme();

        return response()->json([
            'portal' => [
                'variabel' => ThemeVariables::portalPreview($portal),
                'huruf' => $buildStylesheet->fonts($portal),
                'kontras' => ThemeVariables::portalContrasts($portal),
            ],
            'dashboard' => [
                'aksen' => ThemeVariables::dashboard($dashboard)['light']['--brand'] ?? ThemeVariables::DEFAULT_ACCENT,
                'css' => $buildStylesheet->dashboard($dashboard),
                'kontras' => ThemeVariables::dashboardContrasts($dashboard),
            ],
        ]);
    }
}
