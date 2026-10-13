<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\DeleteEntries;
use App\Enums\DashboardModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\DeleteFaqsRequest;
use App\Models\Faq;
use Illuminate\Http\Response;

class FaqDeletionController extends Controller
{
    /**
     * Delete the listed entries; only administrators may (FaqPolicy).
     */
    public function store(DeleteFaqsRequest $request, DeleteEntries $deleteEntries): Response
    {
        $deleteEntries->handle($request->user(), DashboardModule::Faq, Faq::class, $request->ids(), fn (Faq $faq): string => $faq->pertanyaan);

        return response()->noContent();
    }
}
