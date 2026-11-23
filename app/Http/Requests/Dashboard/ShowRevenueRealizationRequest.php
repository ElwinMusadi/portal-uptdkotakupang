<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\RealizationScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The month of the realisation report (D-068), as "YYYY-MM", the day of that
 * month it stops at, as "YYYY-MM-DD", when not the whole month (D-070), and
 * the form: "gabungan" (Form I, the default) or "kota-kupang" (Form I.I,
 * D-101).
 */
class ShowRevenueRealizationRequest extends FormRequest
{
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
            'bulan' => ['required', 'date_format:Y-m', 'after_or_equal:2000-01', 'before_or_equal:2099-12'],
            'sampai' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'cakupan' => ['sometimes', 'string', Rule::enum(RealizationScope::class)],
        ];
    }

    /**
     * The day the report stops at belongs to its month.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $validator->errors()->hasAny(['bulan', 'sampai']) && $this->until() !== null
                    && $this->until()->format('Y-m') !== $this->input('bulan')) {
                    $validator->errors()->add('sampai', 'Tanggal batas harus di dalam bulan laporan.');
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
            'bulan.required' => 'Pilih bulan laporan.',
            'bulan.*' => 'Bulan laporan tidak valid.',
            'sampai.*' => 'Tanggal batas laporan tidak valid.',
            'cakupan.*' => 'Pilih realisasi Gabungan atau Kota Kupang.',
        ];
    }

    public function year(): int
    {
        return (int) substr($this->input('bulan'), 0, 4);
    }

    public function month(): int
    {
        return (int) substr($this->input('bulan'), 5, 2);
    }

    /**
     * Which transactions the report counts.
     */
    public function scope(): RealizationScope
    {
        return RealizationScope::from($this->input('cakupan', RealizationScope::Combined->value));
    }

    /**
     * The day the report stops at, or null for the whole month.
     */
    public function until(): ?Carbon
    {
        return $this->filled('sampai') ? Carbon::createFromFormat('!Y-m-d', $this->input('sampai')) : null;
    }
}
