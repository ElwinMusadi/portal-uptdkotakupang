<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\RecordActivity;
use App\Enums\DashboardModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ReorderServicesRequest;
use App\Models\Service;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ServiceOrderController extends Controller
{
    /**
     * Put the listed services first, in the given order, and the others
     * after them in their current order; only administrators may, since the
     * order is the portal's (ServicePolicy). Reordering is not an edit, so
     * "diperbarui" stays as it was.
     */
    public function update(ReorderServicesRequest $request, RecordActivity $recordActivity): Response
    {
        $ids = $request->ids();

        DB::transaction(function () use ($request, $recordActivity, $ids): void {
            $others = Service::query()->whereKeyNot($ids)->orderBy('urutan')->orderBy('id')->pluck('id')->all();

            foreach ([...$ids, ...$others] as $urutan => $id) {
                Service::query()->whereKey($id)->toBase()->update(['urutan' => $urutan]);
            }

            $recordActivity->handle($request->user(), DashboardModule::Layanan, 'mengubah urutan', count($ids).' layanan');
        });

        return response()->noContent();
    }
}
