<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\RecordActivity;
use App\Enums\DashboardModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ReorderFaqsRequest;
use App\Models\Faq;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class FaqOrderController extends Controller
{
    /**
     * Put the listed entries first, in the given order, and the others after
     * them in their current order (D-003). Reordering is not an edit, so
     * "diperbarui" stays as it was.
     */
    public function update(ReorderFaqsRequest $request, RecordActivity $recordActivity): Response
    {
        $ids = $request->ids();

        DB::transaction(function () use ($request, $recordActivity, $ids): void {
            $others = Faq::query()->whereKeyNot($ids)->orderBy('urutan')->orderBy('id')->pluck('id')->all();

            foreach ([...$ids, ...$others] as $urutan => $id) {
                Faq::query()->whereKey($id)->toBase()->update(['urutan' => $urutan]);
            }

            $recordActivity->handle($request->user(), DashboardModule::Faq, 'mengubah urutan', count($ids).' entri');
        });

        return response()->noContent();
    }
}
