<?php

namespace App\Http\Controllers;

use App\Actions\ListPublicFaqs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicFaqController extends Controller
{
    /**
     * List every FAQ entry currently eligible for the public portal. The
     * collection is complete and unpaginated, so query parameters are rejected
     * rather than silently ignored.
     */
    public function __invoke(Request $request, ListPublicFaqs $listPublicFaqs): JsonResponse
    {
        if ($request->query() !== []) {
            throw ValidationException::withMessages([
                'query' => 'Parameter kueri tidak didukung.',
            ]);
        }

        return response()->json($listPublicFaqs->handle());
    }
}
