<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
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
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nip' => ['required', 'string', 'regex:/^\d{18}\z/', Rule::unique(User::class, 'nip')],
            'nama' => ['required', 'string', 'max:255'],
            'surel' => ['nullable', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'peran' => ['required', Rule::enum(UserRole::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nip.required' => 'NIP wajib diisi.',
            'nip.string' => 'NIP harus 18 digit angka.',
            'nip.regex' => 'NIP harus 18 digit angka.',
            'nip.unique' => 'NIP ini sudah terdaftar.',
            'nama.required' => 'Nama wajib diisi.',
            'nama.string' => 'Nama tidak valid.',
            'nama.max' => 'Nama maksimal 255 karakter.',
            'surel.string' => 'Surel tidak valid.',
            'surel.email' => 'Surel tidak valid.',
            'surel.max' => 'Surel maksimal 255 karakter.',
            'surel.unique' => 'Surel ini sudah dipakai akun lain.',
            'peran.required' => 'Pilih peran akun.',
            'peran.enum' => 'Peran tidak valid.',
        ];
    }
}
