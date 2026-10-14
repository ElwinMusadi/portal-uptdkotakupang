<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\DeleteEntries;
use App\Actions\SyncUploads;
use App\Enums\DashboardModule;
use App\Enums\UploadTarget;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\DeleteArticlesRequest;
use App\Models\Article;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;

class ArticleDeletionController extends Controller
{
    /**
     * Delete the listed articles and their covers; only administrators may
     * (ArticlePolicy).
     */
    public function store(DeleteArticlesRequest $request, DeleteEntries $deleteEntries, SyncUploads $syncUploads): Response
    {
        $deleteEntries->handle(
            $request->user(),
            DashboardModule::Berita,
            Article::class,
            $request->ids(),
            fn (Article $article): string => $article->judul,
            fn (Collection $articles) => $syncUploads->handle(UploadTarget::Berita, $articles->pluck('sampul')->all(), []),
        );

        return response()->noContent();
    }
}
