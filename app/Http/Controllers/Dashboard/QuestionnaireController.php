<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\SaveQuestionnaireStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateQuestionnaireRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

/**
 * Pengaturan › Kuesioner (D-098): an administrator opens and closes each
 * questionnaire at /kuesioner. The portal reads the states through
 * PublicQuestionnaireController.
 */
class QuestionnaireController extends Controller
{
    /**
     * Show whether each questionnaire takes answers, and since when.
     */
    public function show(): JsonResponse
    {
        return response()->json(Setting::questionnaires());
    }

    /**
     * Open or close the questionnaires.
     */
    public function update(UpdateQuestionnaireRequest $request, SaveQuestionnaireStatus $saveQuestionnaireStatus): JsonResponse
    {
        return response()->json($saveQuestionnaireStatus->handle($request->user(), $request->opened()));
    }
}
