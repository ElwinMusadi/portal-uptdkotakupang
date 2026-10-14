<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\ServicePointKind;
use App\Models\MobileServiceStop;
use App\Rules\PlainText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A new schedule entry of service outside the office (D-043). What an entry
 * needs depends on its kind: a Samsat Keliling stop has a unit and one
 * date; a fixed outlet has a name and weekdays, with optional first and
 * last dates; a temporary counter has a name, weekdays and both dates. The
 * server derives a stop's weekday from its date and sets the order.
 */
class StoreMobileServiceStopRequest extends FormRequest
{
    /**
     * The longest a temporary counter may run, in days.
     */
    private const int LONGEST_COUNTER = 366;

    /**
     * Determine if the user is authorized to make this request. The route
     * authorizes it through MobileServiceStopPolicy.
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
            'jenis' => ['required', Rule::enum(ServicePointKind::class)],
            'unit' => ['nullable', 'string', Rule::in(MobileServiceStop::UNITS)],
            'nama' => ['nullable', 'string', 'max:120', new PlainText('Nama')],
            'tanggalMulai' => ['nullable', 'date_format:Y-m-d'],
            'tanggalSelesai' => ['nullable', 'date_format:Y-m-d'],
            'hari' => ['nullable', 'list', 'max:7'],
            'hari.*' => ['integer', 'between:0,6', 'distinct'],
            'mulai' => ['required', 'date_format:H:i'],
            'selesai' => ['required', 'date_format:H:i'],
            'lokasi' => ['required', 'string', 'max:120', new PlainText('Lokasi')],
            'kecamatan' => ['required', 'string', 'max:60', new PlainText('Kecamatan')],
            'layanan' => ['required', 'string', 'max:80', new PlainText('Layanan')],
            'catatan' => ['nullable', 'string', 'max:300', new PlainText('Catatan')],
            'aktif' => ['required', 'boolean'],
        ];
    }

    /**
     * Check the entry as it will be stored, the values sent over the current
     * ones when editing: the end time after the start, and what its kind
     * needs.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $stop = $this->entry();

                if (! $validator->errors()->hasAny(['mulai', 'selesai'])
                    && is_string($stop['mulai']) && is_string($stop['selesai']) && $stop['selesai'] <= $stop['mulai']) {
                    $validator->errors()->add('selesai', 'Jam selesai harus setelah jam mulai.');
                }

                $kind = is_string($stop['jenis']) ? ServicePointKind::tryFrom($stop['jenis']) : null;

                if ($kind === null || $validator->errors()->hasAny(['unit', 'nama', 'tanggalMulai', 'tanggalSelesai', 'hari', 'hari.*'])) {
                    return;
                }

                foreach ($this->problems($kind, $stop) as $field => $message) {
                    $validator->errors()->add($field, $message);
                }
            },
        ];
    }

    /**
     * The entry's attributes to store, after validation.
     *
     * @return array<string, mixed>
     */
    public function stop(): array
    {
        $stop = $this->entry();
        $kind = ServicePointKind::from($stop['jenis']);
        $plain = collect($this->validated())->only(['mulai', 'selesai', 'lokasi', 'kecamatan', 'layanan', 'catatan', 'aktif'])->all();

        if ($kind === ServicePointKind::Keliling) {
            $date = Carbon::parse($stop['tanggalMulai']);

            return [
                ...$plain,
                'jenis' => $kind,
                'unit' => $stop['unit'],
                'nama' => '',
                'tanggal_mulai' => $date->toDateString(),
                'tanggal_selesai' => $date->toDateString(),
                'hari' => [$date->dayOfWeek],
            ];
        }

        $days = array_values(array_unique(array_map('intval', $stop['hari'])));
        sort($days);

        return [
            ...$plain,
            'jenis' => $kind,
            'unit' => null,
            'nama' => (string) $stop['nama'],
            'tanggal_mulai' => $stop['tanggalMulai'] ?: null,
            'tanggal_selesai' => $stop['tanggalSelesai'] ?: null,
            'hari' => $days,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'jenis.required' => 'Pilih jenis layanan.',
            'jenis.enum' => 'Jenis layanan tidak dikenal.',
            'unit.string' => 'Unit Samsat Keliling tidak dikenal.',
            'unit.in' => 'Unit Samsat Keliling tidak dikenal.',
            'nama.string' => 'Nama tidak valid.',
            'nama.max' => 'Nama maksimal :max karakter.',
            'tanggalMulai.date_format' => 'Tanggal tidak valid.',
            'tanggalSelesai.date_format' => 'Tanggal selesai tidak valid.',
            'hari.list' => 'Hari layanan tidak valid.',
            'hari.max' => 'Hari layanan tidak valid.',
            'hari.*.integer' => 'Hari layanan tidak valid.',
            'hari.*.between' => 'Hari layanan tidak valid.',
            'hari.*.distinct' => 'Setiap hari cukup dipilih sekali.',
            'mulai.required' => 'Isi jam mulai.',
            'mulai.date_format' => 'Jam mulai tidak valid.',
            'selesai.required' => 'Isi jam selesai.',
            'selesai.date_format' => 'Jam selesai tidak valid.',
            'lokasi.required' => 'Lokasi wajib diisi.',
            'lokasi.string' => 'Lokasi tidak valid.',
            'lokasi.max' => 'Lokasi maksimal :max karakter.',
            'kecamatan.required' => 'Kecamatan wajib diisi.',
            'kecamatan.string' => 'Kecamatan tidak valid.',
            'kecamatan.max' => 'Kecamatan maksimal :max karakter.',
            'layanan.required' => 'Layanan wajib diisi.',
            'layanan.string' => 'Layanan tidak valid.',
            'layanan.max' => 'Layanan maksimal :max karakter.',
            'catatan.string' => 'Catatan tidak valid.',
            'catatan.max' => 'Catatan maksimal :max karakter.',
            'aktif.required' => 'Tentukan apakah jadwal ini ditampilkan.',
            'aktif.boolean' => 'Pilihan "Tampilkan di portal" tidak valid.',
        ];
    }

    /**
     * What the kind of entry is missing, by field.
     *
     * @param  array<string, mixed>  $stop
     * @return array<string, string>
     */
    private function problems(ServicePointKind $kind, array $stop): array
    {
        $problems = [];
        $first = is_string($stop['tanggalMulai']) && $stop['tanggalMulai'] !== '' ? Carbon::parse($stop['tanggalMulai']) : null;
        $last = is_string($stop['tanggalSelesai']) && $stop['tanggalSelesai'] !== '' ? Carbon::parse($stop['tanggalSelesai']) : null;
        $days = is_array($stop['hari']) ? $stop['hari'] : [];

        if ($kind === ServicePointKind::Keliling) {
            if (! is_string($stop['unit']) || $stop['unit'] === '') {
                $problems['unit'] = 'Pilih unit Samsat Keliling.';
            }
            if ($first === null) {
                $problems['tanggalMulai'] = 'Pilih tanggal layanan.';
            }

            return $problems;
        }

        if ((string) $stop['nama'] === '') {
            $problems['nama'] = $kind === ServicePointKind::Gerai ? 'Isi nama gerai.' : 'Isi nama loket.';
        }

        if ($kind === ServicePointKind::Sementara) {
            if ($first === null) {
                $problems['tanggalMulai'] = 'Isi tanggal mulai.';
            }
            if ($last === null) {
                $problems['tanggalSelesai'] = 'Isi tanggal selesai.';
            }
        }

        if ($first !== null && $last !== null) {
            if ($last->lt($first)) {
                $problems['tanggalSelesai'] = 'Tanggal selesai tidak boleh sebelum tanggal mulai.';
            } elseif ($kind === ServicePointKind::Sementara && $first->diffInDays($last) >= self::LONGEST_COUNTER) {
                $problems['tanggalSelesai'] = 'Loket sementara paling lama satu tahun.';
            }
        }

        if ($days === []) {
            $problems['hari'] = 'Pilih hari layanan.';
        } elseif ($first !== null && $last !== null && $last->gte($first) && ! $this->hasDayBetween($days, $first, $last)) {
            $problems['hari'] = 'Tidak ada hari terpilih di antara tanggal mulai dan selesai.';
        }

        return $problems;
    }

    /**
     * Whether one of the weekdays falls between the two dates.
     *
     * @param  array<int, mixed>  $days
     */
    private function hasDayBetween(array $days, Carbon $first, Carbon $last): bool
    {
        for ($date = $first->copy(); $date->lte($last) && $date->lt($first->copy()->addDays(7)); $date->addDay()) {
            if (in_array($date->dayOfWeek, array_map('intval', $days), true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The entry as it will be stored, in the request's field names: the
     * values sent over the current ones.
     *
     * @return array{jenis: mixed, unit: mixed, nama: mixed, tanggalMulai: mixed, tanggalSelesai: mixed, hari: mixed, mulai: mixed, selesai: mixed}
     */
    private function entry(): array
    {
        $current = $this->currentStop();
        $stored = [
            'jenis' => $current?->jenis->value,
            'unit' => $current?->unit,
            'nama' => $current?->nama,
            'tanggalMulai' => $current?->tanggal_mulai?->toDateString(),
            'tanggalSelesai' => $current?->tanggal_selesai?->toDateString(),
            'hari' => $current?->hari,
            'mulai' => $current?->mulai,
            'selesai' => $current?->selesai,
        ];

        foreach (array_keys($stored) as $field) {
            if ($this->has($field)) {
                $stored[$field] = $this->input($field);
            }
        }

        return $stored;
    }

    /**
     * The entry being edited; none when adding one.
     */
    protected function currentStop(): ?MobileServiceStop
    {
        return null;
    }
}
