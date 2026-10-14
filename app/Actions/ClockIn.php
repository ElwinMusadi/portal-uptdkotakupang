<?php

namespace App\Actions;

use App\Models\FieldActivity;
use App\Models\FieldAttendance;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClockIn
{
    /**
     * Record an officer's clock-in at the server's time and keep the selfie
     * (D-034). A second clock-in sent at the same moment is refused by the
     * database and answered like any repeated clock-in.
     *
     * @throws ValidationException
     */
    public function handle(User $user, FieldActivity $activity, Upload $selfie, float $lat, float $lng, int $accuracy, int $distance): FieldAttendance
    {
        try {
            return DB::transaction(function () use ($user, $activity, $selfie, $lat, $lng, $accuracy, $distance): FieldAttendance {
                $attendance = new FieldAttendance;
                $attendance->forceFill([
                    'field_activity_id' => $activity->id,
                    'user_id' => $user->id,
                    'waktu' => now(),
                    'lat' => round($lat, 6),
                    'lng' => round($lng, 6),
                    'akurasi' => $accuracy,
                    'jarak' => $distance,
                    'foto' => $selfie->url(),
                ])->save();
                $selfie->markUsed();

                return $attendance;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['kegiatan' => 'Anda sudah clock-in di kegiatan ini.']);
        }
    }
}
