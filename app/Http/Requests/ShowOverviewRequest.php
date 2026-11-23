<?php

namespace App\Http\Requests;

use App\Actions\BuildRevenueReport;
use App\Http\Requests\Dashboard\Concerns\FiltersRevenueTransactions;
use App\Http\Requests\Dashboard\ShowRevenueReportRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

/**
 * The period and filters of the Ikhtisar page (D-099, D-100): a range of
 * dates of at most a year, or none for the month of the latest STS date
 * through that date, narrowed by the dashboard report's filters. Nothing
 * else is accepted.
 */
class ShowOverviewRequest extends FormRequest
{
    use FiltersRevenueTransactions;

    /**
     * Determine if the user is authorized to make this request; the route
     * answers only while an administrator has the page open
     * (EnsureOverviewIsOpen).
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
            'dari' => ['required_with:sampai', 'date_format:Y-m-d'],
            'sampai' => ['required_with:dari', 'date_format:Y-m-d', 'after_or_equal:dari'],
            ...$this->filterRules(),
        ];
    }

    /**
     * Unknown parameters are refused rather than ignored, and a range covers
     * at most a year, like the dashboard's report.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $known = ['dari', 'sampai', 'tunggakan', ...array_keys(BuildRevenueReport::DIMENSIONS)];
                if (array_diff(array_keys($this->query()), $known) !== []) {
                    $validator->errors()->add('query', 'Parameter kueri tidak didukung.');
                }
            },
            function (Validator $validator): void {
                if ($this->from() !== null && ! $validator->errors()->hasAny(['dari', 'sampai'])
                    && $this->from()->diffInDays($this->until()) + 1 > ShowRevenueReportRequest::MAX_DAYS) {
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
            'dari.required_with' => 'Pilih tanggal awal laporan.',
            'dari.date_format' => 'Tanggal awal laporan tidak valid.',
            'sampai.required_with' => 'Pilih tanggal akhir laporan.',
            'sampai.date_format' => 'Tanggal akhir laporan tidak valid.',
            'sampai.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
            ...$this->filterMessages(),
        ];
    }

    /**
     * The first day of the range, or null for the month of the latest STS
     * date.
     */
    public function from(): ?Carbon
    {
        return $this->filled('dari') ? Carbon::createFromFormat('!Y-m-d', $this->input('dari')) : null;
    }

    /**
     * The last day of the range, or null for the month of the latest STS
     * date.
     */
    public function until(): ?Carbon
    {
        return $this->filled('sampai') ? Carbon::createFromFormat('!Y-m-d', $this->input('sampai')) : null;
    }
}
