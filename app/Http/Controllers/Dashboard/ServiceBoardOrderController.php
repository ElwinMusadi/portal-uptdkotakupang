<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\RecordActivity;
use App\Enums\DashboardModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ArrangeServiceBoardRequest;
use App\Http\Resources\ServiceBoardOrderResource;
use App\Models\ServiceBoardOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ServiceBoardOrderController extends Controller
{
    /**
     * The time zone the schedule is kept in.
     */
    private const string TIME_ZONE = 'Asia/Makassar';

    /**
     * The homepage board orders from today (WITA), earliest date first
     * (D-053). Past dates are no longer shown and are left out.
     */
    public function index(): AnonymousResourceCollection
    {
        return ServiceBoardOrderResource::collection(
            ServiceBoardOrder::query()
                ->whereDate('tanggal', '>=', Carbon::now(self::TIME_ZONE)->toDateString())
                ->orderBy('tanggal')
                ->get(),
        );
    }

    /**
     * Save the board order of one date, or forget it when the list is empty
     * so the date goes back to time order. The order and one activity log
     * entry are saved together; the entries themselves are not edited. The
     * answer is 200 even when the date had no order yet.
     */
    public function update(ArrangeServiceBoardRequest $request, RecordActivity $recordActivity): JsonResponse
    {
        $day = $request->day();
        $ids = $request->ids();

        $order = DB::transaction(function () use ($request, $recordActivity, $day, $ids): ServiceBoardOrder {
            if ($ids === []) {
                ServiceBoardOrder::query()->whereDate('tanggal', $day)->delete();
                $recordActivity->handle($request->user(), DashboardModule::Keliling, 'mengubah urutan', "papan Beranda {$day} kembali urut jam");

                return new ServiceBoardOrder(['tanggal' => $day, 'urutan' => []]);
            }

            $order = ServiceBoardOrder::query()->updateOrCreate(['tanggal' => $day], ['urutan' => $ids]);
            $recordActivity->handle($request->user(), DashboardModule::Keliling, 'mengubah urutan', count($ids)." entri papan Beranda {$day}");

            return $order;
        });

        return (new ServiceBoardOrderResource($order))->response()->setStatusCode(JsonResponse::HTTP_OK);
    }
}
