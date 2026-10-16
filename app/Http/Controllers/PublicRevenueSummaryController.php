<?php

namespace App\Http\Controllers;

use App\Actions\BuildPublicRevenueSummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicRevenueSummaryController extends Controller
{
    /**
     * The year's revenue for the homepage's transparency dashboard, from the
     * imports shown on the portal (D-055). Query parameters are rejected
     * rather than silently ignored.
     */
    public function __invoke(Request $request, BuildPublicRevenueSummary $buildSummary): JsonResponse
    {
        if ($request->query() !== []) {
            throw ValidationException::withMessages([
                'query' => 'Parameter kueri tidak didukung.',
            ]);
        }

        return response()->json($buildSummary->handle());
    }
}
