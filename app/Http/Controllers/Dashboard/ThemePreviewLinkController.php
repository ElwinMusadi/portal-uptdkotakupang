<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateThemeRequest;
use App\Support\ThemePreview;
use Illuminate\Http\JsonResponse;

class ThemePreviewLinkController extends Controller
{
    /**
     * Make a link that shows the unsaved portal theme on the portal itself
     * (D-057), only in the browser that opens it and for
     * ThemePreview::MINUTES. Visitors keep seeing the saved theme.
     */
    public function store(UpdateThemeRequest $request): JsonResponse
    {
        $token = ThemePreview::store($request->theme()['portal']);

        return response()->json([
            'tautan' => route('tema.pratinjau', $token),
            'berlaku' => now()->addMinutes(ThemePreview::MINUTES)->toIso8601String(),
        ], 201);
    }
}
