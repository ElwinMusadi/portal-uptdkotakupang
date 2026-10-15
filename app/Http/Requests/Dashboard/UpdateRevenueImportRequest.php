<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shows an import on the portal's Laporan Penerimaan page or takes it off
 * (D-048).
 */
class UpdateRevenueImportRequest extends FormRequest
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
            'tampil' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tampil.required' => 'Pilih apakah impor ini tampil di portal.',
            'tampil.boolean' => 'Pilih apakah impor ini tampil di portal.',
        ];
    }
}
