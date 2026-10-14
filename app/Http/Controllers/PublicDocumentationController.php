<?php

namespace App\Http\Controllers;

use App\Actions\ListPublicDocumentations;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicDocumentationController extends Controller
{
    /**
     * List the published activities and their photos for the public
     * portal. Query parameters are rejected rather than silently ignored.
     */
    public function __invoke(Request $request, ListPublicDocumentations $listDocumentations): JsonResponse
    {
        if ($request->query() !== []) {
            throw ValidationException::withMessages([
                'query' => 'Parameter kueri tidak didukung.',
            ]);
        }

        return response()->json($listDocumentations->handle());
    }
}
