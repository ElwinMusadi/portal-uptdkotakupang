<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\ClockIn;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ClockInRequest;
use App\Models\FieldActivity;
use App\Models\FieldAttendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The officer's side of Absensi Lapangan, used by the /absen app (D-034).
 */
class ClockInController extends Controller
{
    /**
     * Today's (WITA) activities assigned to the signed-in officer, each
     * with the point and radius to clock in around and the officer's
     * clock-in, if any.
     */
    public function index(Request $request): JsonResponse
    {
        $today = now(FieldActivity::TIME_ZONE)->toDateString();
        $activities = $request->user()->fieldActivities()
            ->whereDate('tanggal', $today)
            ->with(['attendances' => fn ($query) => $query->where('user_id', $request->user()->id)])
            ->orderBy('nama')
            ->get();

        return response()->json([
            'tanggal' => $today,
            'kegiatan' => $activities->map(fn (FieldActivity $activity): array => [
                'id' => $activity->id,
                'nama' => $activity->nama,
                'tempat' => $activity->tempat,
                'lat' => $activity->lat,
                'lng' => $activity->lng,
                'radius' => $activity->radius,
                'catatan' => $activity->catatan,
                'absen' => $this->attendance($activity->attendances->first()),
            ])->values()->all(),
        ]);
    }

    /**
     * Clock in at an activity (ClockInRequest decides whether the officer
     * may).
     */
    public function store(ClockInRequest $request, FieldActivity $activity, ClockIn $clockIn): JsonResponse
    {
        $attendance = $clockIn->handle(
            $request->user(),
            $activity,
            $request->selfie(),
            (float) $request->input('lat'),
            (float) $request->input('lng'),
            (int) ceil((float) $request->input('akurasi')),
            $request->distance(),
        );

        return response()->json($this->attendance($attendance), 201);
    }

    /**
     * @return ?array{waktu: string, jarak: int, akurasi: int}
     */
    private function attendance(?FieldAttendance $attendance): ?array
    {
        return $attendance === null ? null : [
            'waktu' => $attendance->waktu->toIso8601String(),
            'jarak' => $attendance->jarak,
            'akurasi' => $attendance->akurasi,
        ];
    }
}
