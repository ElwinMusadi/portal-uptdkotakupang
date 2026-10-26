<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\GenerateArticleDraft;
use App\Exceptions\AiRequestFailed;
use App\Exceptions\SourceUnreadable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\GenerateArticleDraftRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * An AI draft for the editor of Berita & Pengumuman (D-085). Nothing is
 * saved: the dashboard fills its form with the answer.
 */
class ArticleDraftController extends Controller
{
    public function __invoke(GenerateArticleDraftRequest $request, GenerateArticleDraft $generateArticleDraft): JsonResponse
    {
        try {
            return response()->json($generateArticleDraft->handle(
                $request->user(),
                $request->validated('tautan'),
                $request->documents(),
                $request->validated('kategori'),
                $request->validated('petunjuk'),
            ));
        } catch (SourceUnreadable $exception) {
            throw ValidationException::withMessages([$exception->field => $exception->getMessage()]);
        } catch (AiRequestFailed $exception) {
            return response()->json(['message' => $exception->getMessage()], match ($exception->reason) {
                'not_configured' => 503,
                'timeout' => 504,
                default => 502,
            });
        }
    }
}
