<?php

namespace App\Http\Controllers;

use App\Actions\ShowPublicServiceBoard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicServiceBoardController extends Controller
{
    /**
     * Show the service board for the public portal. Query parameters are
     * rejected rather than silently ignored.
     */
    public function __invoke(Request $request, ShowPublicServiceBoard $showServiceBoard): JsonResponse
    {
        if ($request->query() !== []) {
            throw ValidationException::withMessages([
                'query' => 'Parameter kueri tidak didukung.',
            ]);
        }

        return response()->json($showServiceBoard->handle());
    }
}
