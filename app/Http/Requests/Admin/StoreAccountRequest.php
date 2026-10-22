<?php

namespace App\Http\Requests\Admin;

use App\Enums\DashboardModule;
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
     * Get the validation rules that apply to the request. An account picked
     * from SIKAP (D-075) takes its name from SIKAP, so a name sent with it is
     * ignored; without "sumber" the account is entered by hand, as for staff
     * from outside UPTD (D-051).
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'sumber' => ['sometimes', 'nullable', Rule::in(['manual', 'sikap'])],
            'nip' => ['required', 'string', 'regex:/^\d{18}\z/', Rule::unique(User::class, 'nip')],
            'nama' => [Rule::excludeIf(fn (): bool => $this->input('sumber') === 'sikap'), 'required', 'string', 'max:255'],
            'surel' => ['nullable', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'peran' => ['required', Rule::enum(UserRole::class)],
            'modul' => ['sometimes', 'list'],
            'modul.*' => ['string', Rule::enum(DashboardModule::class)->only(DashboardModule::assignable())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sumber.in' => 'Sumber data akun tidak dikenal.',
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
            'modul.list' => 'Daftar modul tidak valid.',
            'modul.*.string' => 'Modul tidak dikenal.',
            'modul.*.enum' => 'Modul tidak dikenal.',
        ];
    }

    /**
     * Whether the administrator picked the employee from SIKAP.
     */
    public function fromSikap(): bool
    {
        return $this->validated('sumber') === 'sikap';
    }
}
