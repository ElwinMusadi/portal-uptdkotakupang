<?php

namespace App\Http\Controllers;

use App\Actions\ListPublicArticles;
use App\Actions\ShowPublicArticle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicArticleController extends Controller
{
    /**
     * List the published articles for the public portal. Query parameters
     * are rejected rather than silently ignored.
     */
    public function index(Request $request, ListPublicArticles $listArticles): JsonResponse
    {
        $this->rejectQueryParameters($request);

        return response()->json($listArticles->handle());
    }

    /**
     * Show one published article for the public portal.
     */
    public function show(Request $request, string $slug, ShowPublicArticle $showArticle): JsonResponse
    {
        $this->rejectQueryParameters($request);

        return response()->json($showArticle->handle($slug));
    }

    /**
     * @throws ValidationException
     */
    private function rejectQueryParameters(Request $request): void
    {
        if ($request->query() !== []) {
            throw ValidationException::withMessages([
                'query' => 'Parameter kueri tidak didukung.',
            ]);
        }
    }
}
