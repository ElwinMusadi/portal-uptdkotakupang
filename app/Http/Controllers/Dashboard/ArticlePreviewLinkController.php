<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Support\ArticlePreview;
use Illuminate\Http\JsonResponse;

class ArticlePreviewLinkController extends Controller
{
    /**
     * Make a preview link of the article as it is saved (D-086), valid for
     * ArticlePreview::HOURS for anyone who has it.
     */
    public function store(Article $article): JsonResponse
    {
        return response()->json(ArticlePreview::link($article), 201);
    }
}
