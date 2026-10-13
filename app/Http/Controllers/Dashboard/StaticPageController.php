<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\RecordActivity;
use App\Enums\DashboardModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateStaticPageRequest;
use App\Http\Resources\StaticPageResource;
use App\Models\StaticPage;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * The portal's static pages as the dashboard edits them (D-024). Each one is
 * a page of the portal, so they are not added or deleted here. The portal
 * reads them through PublicStaticPageController.
 */
class StaticPageController extends Controller
{
    /**
     * List the pages in display order.
     */
    public function index(): AnonymousResourceCollection
    {
        return StaticPageResource::collection(StaticPage::query()->orderBy('urutan')->orderBy('id')->get());
    }

    /**
     * Show one page.
     */
    public function show(StaticPage $page): StaticPageResource
    {
        return new StaticPageResource($page);
    }

    /**
     * Change a page's texts. The page and its activity log entry are saved
     * together; an unchanged page is neither saved nor logged.
     */
    public function update(UpdateStaticPageRequest $request, StaticPage $page, RecordActivity $recordActivity): StaticPageResource
    {
        DB::transaction(function () use ($request, $page, $recordActivity): void {
            $page->fill($request->content());

            if ($page->isDirty()) {
                $page->save();
                $recordActivity->handle($request->user(), DashboardModule::Halaman, 'memperbarui', $page->name());
            }
        });

        return new StaticPageResource($page);
    }
}
