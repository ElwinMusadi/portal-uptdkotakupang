<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\RecordActivity;
use App\Actions\SyncUploads;
use App\Actions\UpdateWorkflowEntry;
use App\Enums\DashboardModule;
use App\Enums\UploadTarget;
use App\Enums\WorkflowStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreArticleRequest;
use App\Http\Requests\Dashboard\UpdateArticleRequest;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Berita & Pengumuman as the dashboard manages it (D-031). The public
 * portal reads only published articles through PublicArticleController.
 */
class ArticleController extends Controller
{
    public function __construct(private SyncUploads $syncUploads) {}

    /**
     * List every article, newest first.
     */
    public function index(): AnonymousResourceCollection
    {
        return ArticleResource::collection(Article::query()->orderByDesc('tanggal')->orderByDesc('id')->get());
    }

    /**
     * Show one article.
     */
    public function show(Article $article): ArticleResource
    {
        return new ArticleResource($article);
    }

    /**
     * Add an article as a draft, prepared by the signed-in employee. The
     * article, its activity log entry and its cover's use are saved
     * together.
     */
    public function store(StoreArticleRequest $request, RecordActivity $recordActivity): JsonResponse
    {
        $article = DB::transaction(function () use ($request, $recordActivity): Article {
            $article = new Article($request->content());
            $article->forceFill(['terbit_pada' => null, 'riwayat' => []]);
            $article->moveTo(WorkflowStatus::Draft, $request->user()->name, 'menyiapkan draf');
            $article->save();
            $recordActivity->handle($request->user(), DashboardModule::Berita, 'menambahkan', $article->judul);
            $this->afterSave($article, null);

            return $article;
        });

        return (new ArticleResource($article))->response()->setStatusCode(201);
    }

    /**
     * Change an article, take a workflow step, or both (UpdateWorkflowEntry).
     */
    public function update(UpdateArticleRequest $request, Article $article, UpdateWorkflowEntry $updateEntry): ArticleResource
    {
        $cover = $article->sampul;

        return new ArticleResource($updateEntry->handle(
            $request->user(),
            $article,
            DashboardModule::Berita,
            'judul',
            $request->content(),
            $request->nextStatus(),
            $request->revisionNote(),
            fn (Article $article) => $this->afterSave($article, $cover),
        ));
    }

    /**
     * Keep the cover in use and delete the one it replaced, and keep a
     * single main article: marking one clears the mark on the others
     * without changing their update time.
     */
    private function afterSave(Article $article, ?string $previousCover): void
    {
        $this->syncUploads->handle(UploadTarget::Berita, [$previousCover], [$article->sampul]);

        if ($article->sorotan) {
            Article::query()->whereKeyNot($article->getKey())->where('sorotan', true)->toBase()->update(['sorotan' => false]);
        }
    }
}
