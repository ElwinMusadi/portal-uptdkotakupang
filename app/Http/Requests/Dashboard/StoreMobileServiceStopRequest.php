<?php

namespace App\Http\Requests\Dashboard;

use App\Models\MobileServiceStop;
use App\Rules\PlainText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A new Samsat Keliling stop. The server sets the order.
 */
class StoreMobileServiceStopRequest extends FormRequest
{
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
            'hari' => ['required', 'integer', Rule::in(array_keys(MobileServiceStop::DAYS))],
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
     * The stop must end after it starts, comparing the times that will be
     * stored: the ones sent, or the current ones when editing.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['mulai', 'selesai'])) {
                    return;
                }

                $mulai = $this->input('mulai', $this->currentStop()?->mulai);
                $selesai = $this->input('selesai', $this->currentStop()?->selesai);

                if (is_string($mulai) && is_string($selesai) && $selesai <= $mulai) {
                    $validator->errors()->add('selesai', 'Jam selesai harus setelah jam mulai.');
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
            'hari.required' => 'Pilih hari.',
            'hari.integer' => 'Hari tidak valid.',
            'hari.in' => 'Samsat Keliling melayani Senin sampai Sabtu.',
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
     * The stop being edited; none when adding one.
     */
    protected function currentStop(): ?MobileServiceStop
    {
        return null;
    }
}
