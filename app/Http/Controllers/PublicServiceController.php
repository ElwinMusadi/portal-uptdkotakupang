<?php

namespace App\Http\Controllers;

use App\Actions\ListPublicServices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicServiceController extends Controller
{
    /**
     * List the published services and their requirements for the public
     * portal. Query parameters are rejected rather than silently ignored.
     */
    public function __invoke(Request $request, ListPublicServices $listServices): JsonResponse
    {
        if ($request->query() !== []) {
            throw ValidationException::withMessages([
                'query' => 'Parameter kueri tidak didukung.',
            ]);
        }

        return response()->json($listServices->handle());
    }
}
