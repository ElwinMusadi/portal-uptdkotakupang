<?php

namespace App\Http\Controllers;

use App\Actions\ListPublicAgencyApps;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicAgencyAppController extends Controller
{
    /**
     * List the apps and websites for the public portal (D-054). Query
     * parameters are rejected rather than silently ignored.
     */
    public function __invoke(Request $request, ListPublicAgencyApps $listApps): JsonResponse
    {
        if ($request->query() !== []) {
            throw ValidationException::withMessages([
                'query' => 'Parameter kueri tidak didukung.',
            ]);
        }

        return response()->json(['aplikasi' => $listApps->handle()]);
    }
}
