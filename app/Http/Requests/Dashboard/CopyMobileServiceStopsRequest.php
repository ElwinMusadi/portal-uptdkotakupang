<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

/**
 * The week of Samsat Keliling stops to copy to the following week (D-043),
 * named by its Monday.
 */
class CopyMobileServiceStopsRequest extends FormRequest
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
        ];
    }

    /**
     * The week must start on a Monday.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $validator->errors()->has('dari') && ! $this->week()->isMonday()) {
                    $validator->errors()->add('dari', 'Minggu yang disalin harus dimulai hari Senin.');
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
            'dari.required' => 'Pilih minggu yang disalin.',
            'dari.date_format' => 'Tanggal awal minggu tidak valid.',
        ];
    }

    /**
     * The Monday of the week to copy.
     */
    public function week(): Carbon
    {
        return Carbon::createFromFormat('!Y-m-d', (string) $this->input('dari'));
    }
}
