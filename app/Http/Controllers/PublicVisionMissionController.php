<?php

namespace App\Http\Controllers;

use App\Actions\ShowPublicVisionMission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicVisionMissionController extends Controller
{
    /**
     * Show the Visi & Misi page's content for the public portal. Query
     * parameters are rejected rather than silently ignored.
     */
    public function __invoke(Request $request, ShowPublicVisionMission $showVisionMission): JsonResponse
    {
        if ($request->query() !== []) {
            throw ValidationException::withMessages([
                'query' => 'Parameter kueri tidak didukung.',
            ]);
        }

        return response()->json($showVisionMission->handle());
    }
}
