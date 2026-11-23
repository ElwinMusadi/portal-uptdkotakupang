<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\SaveOverviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateOverviewStatusRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

/**
 * Pengaturan › Halaman Ikhtisar (D-100): an administrator opens the page at
 * /laporan-penerimaan to anyone, without a link of its own, and closes it
 * again. GET /api/ikhtisar answers only while it is open (EnsureOverviewIsOpen).
 */
class OverviewStatusController extends Controller
{
    /**
     * Whether the page is open, since when, and its address.
     */
    public function show(): JsonResponse
    {
        return $this->status(Setting::overview());
    }

    /**
     * Open or close the page.
     */
    public function update(UpdateOverviewStatusRequest $request, SaveOverviewStatus $saveOverviewStatus): JsonResponse
    {
        return $this->status($saveOverviewStatus->handle($request->user(), $request->open()));
    }

    /**
     * @param  array{aktif: bool, sejak: ?string}  $status
     */
    private function status(array $status): JsonResponse
    {
        return response()->json([...$status, 'alamat' => route('ikhtisar')]);
    }
}
