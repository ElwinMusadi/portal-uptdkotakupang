<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SearchSikapEmployeesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request. The route
     * authorizes it through UserPolicy.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request: part of a name or
     * NIP, within the 2 to 100 characters SIKAP searches for.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'q.required' => 'Ketik minimal 2 huruf nama atau angka NIP.',
            'q.string' => 'Ketik minimal 2 huruf nama atau angka NIP.',
            'q.min' => 'Ketik minimal 2 huruf nama atau angka NIP.',
            'q.max' => 'Pencarian maksimal 100 karakter.',
        ];
    }
}
