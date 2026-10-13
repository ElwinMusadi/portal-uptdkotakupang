<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\RecordActivity;
use App\Enums\DashboardModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\DeleteFaqsRequest;
use App\Models\Faq;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class FaqDeletionController extends Controller
{
    /**
     * Delete the listed entries; only administrators may (FaqPolicy). The
     * deletion and its activity log entry are saved together.
     */
    public function store(DeleteFaqsRequest $request, RecordActivity $recordActivity): Response
    {
        DB::transaction(function () use ($request, $recordActivity): void {
            $faqs = Faq::query()->whereKey($request->ids())->get(['id', 'pertanyaan']);

            if ($faqs->isEmpty()) {
                return;
            }

            Faq::query()->whereKey($faqs->modelKeys())->delete();

            $target = $faqs->count() === 1 ? $faqs->first()->pertanyaan : $faqs->count().' entri';
            $recordActivity->handle($request->user(), DashboardModule::Faq, 'menghapus', $target);
        });

        return response()->noContent();
    }
}
