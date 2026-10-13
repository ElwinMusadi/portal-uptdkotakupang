<?php

namespace App\Http\Requests\Dashboard;

use App\Models\ScheduleAdjustment;
use App\Rules\PlainText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A new schedule adjustment. Each date has at most one.
 */
class StoreScheduleAdjustmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request. The route
     * authorizes it through ScheduleAdjustmentPolicy.
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
            'tanggal' => [
                'required',
                'date_format:Y-m-d',
                Rule::unique('schedule_adjustments', 'tanggal')->ignore($this->currentAdjustment()),
            ],
            'jenis' => ['required', Rule::in([ScheduleAdjustment::CLOSED, ScheduleAdjustment::SPECIAL_HOURS])],
            'mulai' => ['nullable', 'date_format:H:i'],
            'selesai' => ['nullable', 'date_format:H:i'],
            'keterangan' => ['required', 'string', 'max:150', new PlainText('Keterangan')],
        ];
    }

    /**
     * Special hours need both times, comparing the values that will be
     * stored: the ones sent, or the current ones when editing.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['jenis', 'mulai', 'selesai'])) {
                    return;
                }

                $current = $this->currentAdjustment();
                $jenis = $this->input('jenis', $current?->jenis);

                if ($jenis !== ScheduleAdjustment::SPECIAL_HOURS) {
                    return;
                }

                $mulai = $this->has('mulai') ? $this->input('mulai') : ($current?->mulai ?: null);
                $selesai = $this->has('selesai') ? $this->input('selesai') : ($current?->selesai ?: null);

                if ($mulai === null) {
                    $validator->errors()->add('mulai', 'Isi jam buka.');
                }

                if ($selesai === null) {
                    $validator->errors()->add('selesai', 'Isi jam tutup.');
                } elseif ($mulai !== null && $selesai <= $mulai) {
                    $validator->errors()->add('selesai', 'Jam tutup harus setelah jam buka.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tanggal.required' => 'Pilih tanggal.',
            'tanggal.date_format' => 'Tanggal tidak valid.',
            'tanggal.unique' => 'Sudah ada penyesuaian untuk tanggal ini.',
            'jenis.required' => 'Pilih jenis perubahan.',
            'jenis.in' => 'Jenis perubahan tidak valid.',
            'mulai.date_format' => 'Jam buka tidak valid.',
            'selesai.date_format' => 'Jam tutup tidak valid.',
            'keterangan.required' => 'Keterangan wajib diisi.',
            'keterangan.string' => 'Keterangan tidak valid.',
            'keterangan.max' => 'Keterangan maksimal :max karakter.',
        ];
    }

    /**
     * The adjustment being edited; none when adding one.
     */
    protected function currentAdjustment(): ?ScheduleAdjustment
    {
        return null;
    }
}
