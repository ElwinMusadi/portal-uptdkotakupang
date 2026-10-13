<?php

namespace App\Http\Controllers;

use App\Actions\ListPublicDocuments;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicDocumentController extends Controller
{
    /**
     * List the published documents for the public portal. Query parameters
     * are rejected rather than silently ignored.
     */
    public function __invoke(Request $request, ListPublicDocuments $listDocuments): JsonResponse
    {
        if ($request->query() !== []) {
            throw ValidationException::withMessages([
                'query' => 'Parameter kueri tidak didukung.',
            ]);
        }

        return response()->json($listDocuments->handle());
    }
}
