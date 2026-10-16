<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The due month a Pemetaan PKB summary covers (D-052): one month of the
 * year, or every month the data holds when omitted.
 */
class ShowPotentialSummaryRequest extends FormRequest
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
            'bulan' => ['sometimes', 'integer', 'between:1,12'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bulan.*' => 'Bulan jatuh tempo harus 1 sampai 12.',
        ];
    }

    /**
     * The due month, or null for every month.
     */
    public function month(): ?int
    {
        return $this->has('bulan') ? (int) $this->validated('bulan') : null;
    }
}
