<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\RecordActivity;
use App\Enums\DashboardModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ReorderUpdateStandardsRequest;
use App\Models\UpdateStandard;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class UpdateStandardOrderController extends Controller
{
    /**
     * Put the listed standards first, in the given order, and the others
     * after them in their current order (D-003). Reordering is not an edit,
     * so "diperbarui" stays as it was.
     */
    public function update(ReorderUpdateStandardsRequest $request, RecordActivity $recordActivity): Response
    {
        $ids = $request->ids();

        DB::transaction(function () use ($request, $recordActivity, $ids): void {
            $others = UpdateStandard::query()->whereKeyNot($ids)->orderBy('urutan')->orderBy('id')->pluck('id')->all();

            foreach ([...$ids, ...$others] as $urutan => $id) {
                UpdateStandard::query()->whereKey($id)->toBase()->update(['urutan' => $urutan]);
            }

            $recordActivity->handle($request->user(), DashboardModule::Pemutakhiran, 'mengubah urutan', count($ids).' entri');
        });

        return response()->noContent();
    }
}
