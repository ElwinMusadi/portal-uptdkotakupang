<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\RecordActivity;
use App\Enums\DashboardModule;
use App\Enums\FaqStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreFaqRequest;
use App\Http\Requests\Dashboard\UpdateFaqRequest;
use App\Http\Resources\FaqResource;
use App\Models\Faq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * FAQ entries as the dashboard manages them (D-011). The public portal reads
 * only eligible entries through PublicFaqController.
 */
class FaqController extends Controller
{
    /**
     * List every entry in display order.
     */
    public function index(): AnonymousResourceCollection
    {
        return FaqResource::collection(Faq::query()->orderBy('urutan')->orderBy('id')->get());
    }

    /**
     * Show one entry.
     */
    public function show(Faq $faq): FaqResource
    {
        return new FaqResource($faq);
    }

    /**
     * Add an entry at the end of the list. The entry and its activity log
     * entry are saved together.
     */
    public function store(StoreFaqRequest $request, RecordActivity $recordActivity): JsonResponse
    {
        $faq = DB::transaction(function () use ($request, $recordActivity): Faq {
            $faq = new Faq($request->validated());
            $faq->urutan = (int) (Faq::query()->max('urutan') ?? -1) + 1;
            $faq->save();

            $recordActivity->handle($request->user(), DashboardModule::Faq, 'menambahkan', $faq->pertanyaan);

            return $faq;
        });

        return (new FaqResource($faq))->response()->setStatusCode(201);
    }

    /**
     * Change an entry. A status change is logged by what it means for the
     * portal; an unchanged entry is not logged.
     */
    public function update(UpdateFaqRequest $request, Faq $faq, RecordActivity $recordActivity): FaqResource
    {
        DB::transaction(function () use ($request, $faq, $recordActivity): void {
            $faq->fill($request->validated())->save();

            if ($faq->wasChanged()) {
                $action = ! $faq->wasChanged('status') ? 'memperbarui' : match ($faq->status) {
                    FaqStatus::Published => 'menerbitkan',
                    FaqStatus::Unpublished => 'menarik dari portal',
                    FaqStatus::Draft => 'mengembalikan ke draf',
                };
                $recordActivity->handle($request->user(), DashboardModule::Faq, $action, $faq->pertanyaan);
            }
        });

        return new FaqResource($faq);
    }
}
