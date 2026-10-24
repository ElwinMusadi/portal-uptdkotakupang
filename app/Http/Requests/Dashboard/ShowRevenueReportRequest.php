<?php

namespace App\Http\Requests\Dashboard;

use App\Http\Requests\Dashboard\Concerns\FiltersRevenueTransactions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

/**
 * The period and filters of the revenue report (D-047): a day, a month or any
 * range up to a year, narrowed by counter, registration type, vehicle and
 * place. Each filter lists the values to keep.
 */
class ShowRevenueReportRequest extends FormRequest
{
    use FiltersRevenueTransactions;

    /**
     * Longest period of one report, in days.
     */
    public const int MAX_DAYS = 366;

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
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'dari' => ['required', 'date_format:Y-m-d'],
            'sampai' => ['required', 'date_format:Y-m-d', 'after_or_equal:dari'],
            ...$this->filterRules(),
        ];
    }

    /**
     * A report covers at most a year.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $validator->errors()->hasAny(['dari', 'sampai']) && $this->from()->diffInDays($this->until()) + 1 > self::MAX_DAYS) {
                    $validator->errors()->add('sampai', 'Rentang laporan paling lama satu tahun.');
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
            ...$this->filterMessages(),
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
}
