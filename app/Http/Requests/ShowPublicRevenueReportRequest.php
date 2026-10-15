<?php

namespace App\Http\Requests;

use App\Actions\BuildPublicRevenueReport;
use App\Http\Requests\Dashboard\ShowRevenueReportRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

/**
 * The period and filters of the portal's revenue report (D-048). Unlike the
 * other public endpoints (D-004), it takes query parameters, but only these:
 * a period of at most a year (the latest published day when omitted) and the
 * values to keep per main filter. Any other parameter is refused.
 */
class ShowPublicRevenueReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
        $rules = [
            'dari' => ['required_with:sampai', 'date_format:Y-m-d'],
            'sampai' => ['required_with:dari', 'date_format:Y-m-d', 'after_or_equal:dari'],
        ];

        foreach (BuildPublicRevenueReport::DIMENSIONS as $filter) {
            $rules[$filter] = ['sometimes', 'list', 'max:100'];
            $rules["{$filter}.*"] = ['string', 'max:80'];
        }

        return $rules;
    }

    /**
     * Refuse unknown parameters, and periods longer than a year.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (array_diff(array_keys($this->query()), ['dari', 'sampai', ...BuildPublicRevenueReport::DIMENSIONS]) !== []) {
                    $validator->errors()->add('query', 'Parameter kueri tidak didukung.');
                }

                if ($this->hasPeriod() && ! $validator->errors()->hasAny(['dari', 'sampai'])
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
            'list' => 'Saringan :attribute tidak valid.',
            'max' => 'Saringan :attribute terlalu panjang.',
            'string' => 'Saringan :attribute tidak valid.',
        ];
    }

    /**
     * The filters as the messages name them.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $names = ['loket' => 'loket', 'jenisDaftar' => 'jenis daftar', 'jenisKendaraan' => 'jenis kendaraan', 'kecamatan' => 'kecamatan', 'kelurahan' => 'kelurahan'];

        $attributes = [];
        foreach ($names as $filter => $name) {
            $attributes[$filter] = $name;
            $attributes["{$filter}.*"] = $name;
        }

        return $attributes;
    }

    /**
     * Whether the request names its period.
     */
    public function hasPeriod(): bool
    {
        return $this->filled(['dari', 'sampai']);
    }

    public function from(): ?Carbon
    {
        return $this->hasPeriod() ? Carbon::createFromFormat('!Y-m-d', $this->input('dari')) : null;
    }

    public function until(): ?Carbon
    {
        return $this->hasPeriod() ? Carbon::createFromFormat('!Y-m-d', $this->input('sampai')) : null;
    }

    /**
     * The chosen values per filter.
     *
     * @return array<string, list<string>>
     */
    public function filters(): array
    {
        return array_filter(
            $this->safe()->only(BuildPublicRevenueReport::DIMENSIONS),
            fn (mixed $value): bool => $value !== [] && $value !== null,
        );
    }
}
