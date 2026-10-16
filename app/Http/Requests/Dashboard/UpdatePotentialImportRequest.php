<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Showing the Pemetaan PKB figures on the public portal or taking them off
 * (D-052 slice 2).
 */
class UpdatePotentialImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request; the route
     * checks PotentialImportPolicy.
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
            'tampil' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tampil.*' => 'Pilih apakah pemetaan tampil di portal.',
        ];
    }
}
