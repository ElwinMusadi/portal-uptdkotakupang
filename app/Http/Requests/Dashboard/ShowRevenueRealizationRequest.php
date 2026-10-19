<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The month of the realisation report (D-068), as "YYYY-MM".
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
}
