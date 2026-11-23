<?php

namespace App\Http\Controllers;

use App\Actions\BuildOverview;
use App\Http\Requests\ShowOverviewRequest;
use Illuminate\Http\JsonResponse;

class OverviewController extends Controller
{
    /**
     * The figures of the Ikhtisar page (D-099, D-100), while it is open
     * (EnsureOverviewIsOpen), of the chosen period and filters.
     */
    public function __invoke(ShowOverviewRequest $request, BuildOverview $overview): JsonResponse
    {
        return response()->json($overview->handle($request->from(), $request->until(), $request->filters()));
    }
}
