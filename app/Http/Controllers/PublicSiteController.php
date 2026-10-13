<?php

namespace App\Http\Controllers;

use App\Actions\ShowPublicSite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicSiteController extends Controller
{
    /**
     * Show the footer texts and announcement bar for the public portal.
     * Query parameters are rejected rather than silently ignored.
     */
    public function __invoke(Request $request, ShowPublicSite $showSite): JsonResponse
    {
        if ($request->query() !== []) {
            throw ValidationException::withMessages([
                'query' => 'Parameter kueri tidak didukung.',
            ]);
        }

        return response()->json($showSite->handle());
    }
}
