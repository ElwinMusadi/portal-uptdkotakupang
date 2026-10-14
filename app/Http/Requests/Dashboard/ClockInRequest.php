<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\UploadTarget;
use App\Models\FieldActivity;
use App\Models\FieldAttendance;
use App\Models\Upload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * An officer's clock-in at a field activity (D-034): the GPS point and its
 * accuracy as the phone reports them, and a selfie the officer has just
 * uploaded. The server decides: clock-in is open on the activity's date
 * (WITA), once per officer, within the radius and with a usable accuracy.
 * The time is the server's.
 */
class ClockInRequest extends FormRequest
{
    /**
     * Minutes a selfie stays usable after its upload.
     */
    public const int SELFIE_MINUTES = 15;

    /**
     * Determine if the user is authorized to make this request. The route
     * authorizes it through FieldActivityPolicy::clockIn.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'foto' => ['required', 'string'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'akurasi' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'foto.required' => 'Ambil swafoto untuk clock-in.',
            'foto.string' => 'Ambil swafoto untuk clock-in.',
            'lat.required' => 'Lokasi GPS belum terekam.',
            'lat.numeric' => 'Lokasi GPS tidak valid.',
            'lat.between' => 'Lokasi GPS tidak valid.',
            'lng.required' => 'Lokasi GPS belum terekam.',
            'lng.numeric' => 'Lokasi GPS tidak valid.',
            'lng.between' => 'Lokasi GPS tidak valid.',
            'akurasi.required' => 'Akurasi GPS belum terekam.',
            'akurasi.numeric' => 'Akurasi GPS tidak valid.',
            'akurasi.min' => 'Akurasi GPS tidak valid.',
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $activity = $this->activity();
                $previous = $activity->attendances()->where('user_id', $this->user()->id)->first();

                if ($previous instanceof FieldAttendance) {
                    $validator->errors()->add('kegiatan', 'Anda sudah clock-in di kegiatan ini pukul '.$previous->waktu->setTimezone(FieldActivity::TIME_ZONE)->format('H.i').' WITA.');

                    return;
                }

                if (! $activity->isOpen()) {
                    $validator->errors()->add('kegiatan', 'Clock-in kegiatan ini hanya dibuka pada tanggal kegiatan.');

                    return;
                }

                $accuracy = (int) ceil((float) $this->input('akurasi'));
                $distance = $this->distance();

                if ($accuracy > FieldActivity::MAX_ACCURACY) {
                    $validator->errors()->add('akurasi', "Akurasi GPS ±{$accuracy} m terlalu rendah; batasnya ±".FieldActivity::MAX_ACCURACY.' m. Pindah ke area terbuka lalu perbarui lokasi.');
                }

                if ($distance > $activity->radius) {
                    $validator->errors()->add('lokasi', "Anda berada {$distance} m dari lokasi kegiatan. Clock-in hanya dalam radius {$activity->radius} m.");
                }

                if ($this->selfie() === null) {
                    $validator->errors()->add('foto', 'Ambil swafoto baru untuk clock-in.');
                }
            },
        ];
    }

    /**
     * The activity clocked in to, from the route.
     */
    public function activity(): FieldActivity
    {
        return $this->route('activity');
    }

    /**
     * Distance in metres from the activity's point to the reported one.
     */
    public function distance(): int
    {
        return $this->activity()->distanceTo((float) $this->input('lat'), (float) $this->input('lng'));
    }

    /**
     * The selfie: an attendance photo this officer uploaded in the last
     * few minutes that no clock-in uses yet.
     */
    public function selfie(): ?Upload
    {
        $url = $this->input('foto');
        $upload = is_string($url) ? Upload::fromUrl($url, UploadTarget::Absensi) : null;

        return $upload !== null
            && $upload->user_id === $this->user()->id
            && $upload->dipakai_pada === null
            && $upload->created_at->gte(now()->subMinutes(self::SELFIE_MINUTES))
            ? $upload
            : null;
    }
}
