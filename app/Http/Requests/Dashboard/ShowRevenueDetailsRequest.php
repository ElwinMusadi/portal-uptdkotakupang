<?php

namespace App\Http\Requests\Dashboard;

use App\Http\Requests\Dashboard\Concerns\FiltersRevenueTransactions;
use App\Models\RevenueTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

/**
 * The day and filters of Rincian penerimaan (D-079): one day of the last 7
 * (WITA), the only days whose transactions keep their details, narrowed like
 * the revenue report.
 */
class ShowRevenueDetailsRequest extends FormRequest
{
    use FiltersRevenueTransactions;

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
            'tanggal' => ['required', 'date_format:Y-m-d'],
            ...$this->filterRules(),
        ];
    }

    /**
     * The day is one of the last 7.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('tanggal')) {
                    return;
                }

                $day = $this->day()->toDateString();
                if ($day < RevenueTransaction::detailsSince()->toDateString() || $day > RevenueTransaction::detailsUntil()->toDateString()) {
                    $validator->errors()->add('tanggal', 'Rincian per transaksi hanya tersedia untuk 7 hari terakhir.');
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
            'tanggal.required' => 'Pilih tanggal rincian.',
            'tanggal.date_format' => 'Tanggal rincian tidak valid.',
            ...$this->filterMessages(),
        ];
    }

    public function day(): Carbon
    {
        return Carbon::createFromFormat('!Y-m-d', $this->input('tanggal'));
    }
}
