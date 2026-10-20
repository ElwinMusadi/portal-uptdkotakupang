<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

/**
 * The period of the Nomoratur report (D-071): a day or a month, at most 31
 * days, for every counter or one of them.
 */
class ShowFormNumberReportRequest extends FormRequest
{
    /**
     * Longest period of one report, in days.
     */
    public const int MAX_DAYS = 31;

    /**
     * Determine if the user is authorized to make this request. The route
     * authorizes it through RevenueImportPolicy.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'dari' => ['required', 'date_format:Y-m-d'],
            'sampai' => ['required', 'date_format:Y-m-d', 'after_or_equal:dari'],
            'loket' => ['sometimes', 'nullable', 'string', 'max:80'],
        ];
    }

    /**
     * A report covers at most a month.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $validator->errors()->hasAny(['dari', 'sampai']) && $this->from()->diffInDays($this->until()) + 1 > self::MAX_DAYS) {
                    $validator->errors()->add('sampai', 'Laporan nomoratur paling lama 31 hari.');
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
            'dari.required' => 'Pilih tanggal awal laporan.',
            'dari.date_format' => 'Tanggal awal laporan tidak valid.',
            'sampai.required' => 'Pilih tanggal akhir laporan.',
            'sampai.date_format' => 'Tanggal akhir laporan tidak valid.',
            'sampai.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
            'loket.*' => 'Loket tidak valid.',
        ];
    }

    public function from(): Carbon
    {
        return Carbon::createFromFormat('!Y-m-d', $this->input('dari'));
    }

    public function until(): Carbon
    {
        return Carbon::createFromFormat('!Y-m-d', $this->input('sampai'));
    }

    /**
     * The counter to report on, or null for every counter.
     */
    public function counter(): ?string
    {
        return $this->filled('loket') ? (string) $this->input('loket') : null;
    }
}
