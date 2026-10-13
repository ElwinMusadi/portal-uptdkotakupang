<?php

namespace App\Http\Controllers;

use App\Actions\ShowPublicHomepage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicHomepageController extends Controller
{
    /**
     * Show the homepage's texts for the public portal. Query parameters are
     * rejected rather than silently ignored.
     */
    public function __invoke(Request $request, ShowPublicHomepage $showHomepage): JsonResponse
    {
        if ($request->query() !== []) {
            throw ValidationException::withMessages([
                'query' => 'Parameter kueri tidak didukung.',
            ]);
        }

        return response()->json($showHomepage->handle());
    }
}
