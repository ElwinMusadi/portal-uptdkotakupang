<?php

namespace App\Http\Requests\Auth;

use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

class ActivateAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request. The signed
     * link itself is checked by the controller.
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
            'sandi' => ['required', 'string', 'max:255', new StrongPassword, 'confirmed:sandi_ulang'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sandi.required' => 'Kata sandi wajib diisi.',
            'sandi.string' => 'Kata sandi tidak valid.',
            'sandi.max' => 'Kata sandi terlalu panjang.',
            'sandi.confirmed' => 'Ulangan kata sandi tidak sama.',
        ];
    }
}
