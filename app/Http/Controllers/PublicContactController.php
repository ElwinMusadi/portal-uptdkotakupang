<?php

namespace App\Http\Controllers;

use App\Actions\ShowPublicContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicContactController extends Controller
{
    /**
     * Show the office's contact details for the public portal. Query
     * parameters are rejected rather than silently ignored.
     */
    public function __invoke(Request $request, ShowPublicContact $showContact): JsonResponse
    {
        if ($request->query() !== []) {
            throw ValidationException::withMessages([
                'query' => 'Parameter kueri tidak didukung.',
            ]);
        }

        return response()->json($showContact->handle());
    }
}
