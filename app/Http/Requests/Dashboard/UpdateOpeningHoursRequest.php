<?php

namespace App\Http\Requests\Dashboard;

use App\Rules\PlainText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * The complete opening hours document: one entry per weekday (0 = Sunday
 * and holidays), the minutes before closing when documents are no longer
 * accepted, and notes for citizens.
 */
class UpdateOpeningHoursRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request. The route
     * authorizes it through SettingPolicy.
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
            'hari' => ['required', 'list', 'size:7'],
            'hari.*' => ['required', 'array'],
            'hari.*.hari' => ['required', 'integer', 'between:0,6', 'distinct'],
            'hari.*.buka' => ['required', 'boolean'],
            'hari.*.mulai' => ['nullable', 'date_format:H:i'],
            'hari.*.selesai' => ['nullable', 'date_format:H:i'],
            'hari.*.catatan' => ['nullable', 'string', 'max:100', new PlainText('Catatan')],
            'batasBerkasMenit' => ['required', 'integer', 'between:0,240'],
            'catatan' => ['nullable', 'string', 'max:300', new PlainText('Catatan untuk warga')],
            'pemberitahuan' => ['nullable', 'string', 'max:200', new PlainText('Pemberitahuan perubahan')],
        ];
    }

    /**
     * An open day needs both times, and closes after it opens.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                foreach ($this->input('hari') as $i => $hari) {
                    if (! filter_var($hari['buka'], FILTER_VALIDATE_BOOL)) {
                        continue;
                    }

                    $mulai = $hari['mulai'] ?? null;
                    $selesai = $hari['selesai'] ?? null;

                    if ($mulai === null) {
                        $validator->errors()->add("hari.{$i}.mulai", 'Isi jam buka.');
                    }

                    if ($selesai === null) {
                        $validator->errors()->add("hari.{$i}.selesai", 'Isi jam tutup.');
                    } elseif ($mulai !== null && $selesai <= $mulai) {
                        $validator->errors()->add("hari.{$i}.selesai", 'Jam tutup harus setelah jam buka.');
                    }
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
            'hari.required' => 'Jam per hari wajib diisi.',
            'hari.list' => 'Jam per hari tidak valid.',
            'hari.size' => 'Jam per hari harus berisi tujuh hari.',
            'hari.*.required' => 'Jam per hari tidak valid.',
            'hari.*.array' => 'Jam per hari tidak valid.',
            'hari.*.hari.required' => 'Hari tidak valid.',
            'hari.*.hari.integer' => 'Hari tidak valid.',
            'hari.*.hari.between' => 'Hari tidak valid.',
            'hari.*.hari.distinct' => 'Setiap hari hanya boleh muncul sekali.',
            'hari.*.buka.required' => 'Tentukan apakah loket buka.',
            'hari.*.buka.boolean' => 'Pilihan buka tidak valid.',
            'hari.*.mulai.date_format' => 'Jam buka tidak valid.',
            'hari.*.selesai.date_format' => 'Jam tutup tidak valid.',
            'hari.*.catatan.string' => 'Catatan tidak valid.',
            'hari.*.catatan.max' => 'Catatan maksimal :max karakter.',
            'batasBerkasMenit.required' => 'Isi batas penerimaan berkas.',
            'batasBerkasMenit.integer' => 'Batas penerimaan berkas harus bilangan bulat.',
            'batasBerkasMenit.between' => 'Batas penerimaan berkas 0 sampai 240 menit.',
            'catatan.string' => 'Catatan untuk warga tidak valid.',
            'catatan.max' => 'Catatan untuk warga maksimal :max karakter.',
            'pemberitahuan.string' => 'Pemberitahuan perubahan tidak valid.',
            'pemberitahuan.max' => 'Pemberitahuan perubahan maksimal :max karakter.',
        ];
    }

    /**
     * The document as it is stored: days from Monday to Sunday, no times on
     * closed days, and empty strings instead of missing notes.
     *
     * @return array{hari: list<array{hari: int, buka: bool, mulai: string, selesai: string, catatan: string}>, batasBerkasMenit: int, catatan: string, pemberitahuan: string}
     */
    public function openingHours(): array
    {
        $hari = array_map(function (array $day): array {
            $buka = filter_var($day['buka'], FILTER_VALIDATE_BOOL);

            return [
                'hari' => (int) $day['hari'],
                'buka' => $buka,
                'mulai' => $buka ? $day['mulai'] : '',
                'selesai' => $buka ? $day['selesai'] : '',
                'catatan' => $day['catatan'] ?? '',
            ];
        }, $this->validated('hari'));

        usort($hari, fn (array $a, array $b): int => ($a['hari'] + 6) % 7 <=> ($b['hari'] + 6) % 7);

        return [
            'hari' => $hari,
            'batasBerkasMenit' => (int) $this->validated('batasBerkasMenit'),
            'catatan' => $this->validated('catatan') ?? '',
            'pemberitahuan' => $this->validated('pemberitahuan') ?? '',
        ];
    }
}
