<?php

namespace App\Http\Controllers;

use App\Actions\BuildOverview;
use App\Http\Requests\ShowOverviewRequest;
use Illuminate\Http\JsonResponse;

class OverviewController extends Controller
{
    /**
     * The figures of the Ikhtisar page (D-099), for the holder of a link
     * (AuthenticateOverviewLink), of the chosen period.
     */
    public function __invoke(ShowOverviewRequest $request, BuildOverview $overview): JsonResponse
    {
        return response()->json($overview->handle($request->period(), $request->from(), $request->until()));
    }
}
