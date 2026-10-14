<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A day of Samsat Keliling stops and the other days to copy it to (D-044).
 */
class CopyMobileServiceStopsToDaysRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request. The route
     * authorizes it through MobileServiceStopPolicy.
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
            'ke' => ['required', 'list', 'max:14'],
            'ke.*' => ['date_format:Y-m-d', 'distinct', 'different:dari'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dari.required' => 'Pilih hari yang disalin.',
            'dari.date_format' => 'Tanggal asal tidak valid.',
            'ke.required' => 'Pilih minimal satu hari tujuan.',
            'ke.list' => 'Hari tujuan tidak valid.',
            'ke.max' => 'Paling banyak 14 hari tujuan sekali salin.',
            'ke.*.date_format' => 'Tanggal tujuan tidak valid.',
            'ke.*.distinct' => 'Setiap hari tujuan cukup dipilih sekali.',
            'ke.*.different' => 'Hari tujuan harus berbeda dari hari asal.',
        ];
    }

    /**
     * The days to copy to, earliest first.
     *
     * @return list<string>
     */
    public function days(): array
    {
        $days = array_values($this->validated('ke'));
        sort($days);

        return $days;
    }
}
