<?php

namespace App\Http\Controllers;

use App\Actions\ShowPublicStaticPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicStaticPageController extends Controller
{
    /**
     * Show a static page of the public portal. Query parameters are rejected
     * rather than silently ignored.
     */
    public function __invoke(Request $request, string $slug, ShowPublicStaticPage $showPage): JsonResponse
    {
        if ($request->query() !== []) {
            throw ValidationException::withMessages([
                'query' => 'Parameter kueri tidak didukung.',
            ]);
        }

        return response()->json($showPage->handle($slug));
    }
}
