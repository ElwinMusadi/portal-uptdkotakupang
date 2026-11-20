<?php

namespace App\Http\Controllers;

use App\Actions\ShowPublicQuestionnaires;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicQuestionnaireController extends Controller
{
    /**
     * Show which questionnaires take answers, for the portal's /kuesioner
     * pages (D-098). Query parameters are rejected rather than silently
     * ignored.
     */
    public function __invoke(Request $request, ShowPublicQuestionnaires $showQuestionnaires): JsonResponse
    {
        if ($request->query() !== []) {
            throw ValidationException::withMessages([
                'query' => 'Parameter kueri tidak didukung.',
            ]);
        }

        return response()->json($showQuestionnaires->handle());
    }
}
