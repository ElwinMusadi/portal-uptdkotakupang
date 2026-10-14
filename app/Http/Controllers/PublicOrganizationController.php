<?php

namespace App\Http\Controllers;

use App\Actions\ShowPublicOrganization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicOrganizationController extends Controller
{
    /**
     * Show the organisation chart for the public portal. Query parameters
     * are rejected rather than silently ignored.
     */
    public function __invoke(Request $request, ShowPublicOrganization $showOrganization): JsonResponse
    {
        if ($request->query() !== []) {
            throw ValidationException::withMessages([
                'query' => 'Parameter kueri tidak didukung.',
            ]);
        }

        return response()->json($showOrganization->handle());
    }
}
