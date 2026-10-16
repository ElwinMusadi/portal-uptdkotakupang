<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\RecordActivity;
use App\Enums\DashboardModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ReorderAgencyAppsRequest;
use App\Models\AgencyApp;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class AgencyAppOrderController extends Controller
{
    /**
     * Put the listed apps first, in the given order, and the others after
     * them in their current order. Reordering is not an edit, so
     * "diperbarui" stays as it was.
     */
    public function update(ReorderAgencyAppsRequest $request, RecordActivity $recordActivity): Response
    {
        $ids = $request->ids();

        DB::transaction(function () use ($request, $recordActivity, $ids): void {
            $others = AgencyApp::query()->whereKeyNot($ids)->orderBy('urutan')->orderBy('id')->pluck('id')->all();

            foreach ([...$ids, ...$others] as $urutan => $id) {
                AgencyApp::query()->whereKey($id)->toBase()->update(['urutan' => $urutan]);
            }

            $recordActivity->handle($request->user(), DashboardModule::Aplikasi, 'mengubah urutan', count($ids).' aplikasi');
        });

        return response()->noContent();
    }
}
