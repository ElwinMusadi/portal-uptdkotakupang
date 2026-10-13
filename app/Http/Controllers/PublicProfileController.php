<?php

namespace App\Http\Controllers;

use App\Actions\ShowPublicProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicProfileController extends Controller
{
    /**
     * Show the Tentang UPTD page's content for the public portal. Query
     * parameters are rejected rather than silently ignored.
     */
    public function __invoke(Request $request, ShowPublicProfile $showProfile): JsonResponse
    {
        if ($request->query() !== []) {
            throw ValidationException::withMessages([
                'query' => 'Parameter kueri tidak didukung.',
            ]);
        }

        return response()->json($showProfile->handle());
    }
}
