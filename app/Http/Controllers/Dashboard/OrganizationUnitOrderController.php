<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\RecordActivity;
use App\Enums\DashboardModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ReorderOrganizationUnitsRequest;
use App\Models\OrganizationUnit;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class OrganizationUnitOrderController extends Controller
{
    /**
     * Put the listed units first, in the given order, and the others after
     * them in their current order. The order is the chart's, left to right.
     * Reordering is not an edit, so "diperbarui" stays as it was.
     */
    public function update(ReorderOrganizationUnitsRequest $request, RecordActivity $recordActivity): Response
    {
        $ids = $request->ids();

        DB::transaction(function () use ($request, $recordActivity, $ids): void {
            $others = OrganizationUnit::query()->whereKeyNot($ids)->orderBy('urutan')->orderBy('id')->pluck('id')->all();

            foreach ([...$ids, ...$others] as $urutan => $id) {
                OrganizationUnit::query()->whereKey($id)->toBase()->update(['urutan' => $urutan]);
            }

            $recordActivity->handle($request->user(), DashboardModule::Unit, 'mengubah urutan', count($ids).' unit');
        });

        return response()->noContent();
    }
}
