<?php

namespace App\Http\Requests\Admin;

use App\Enums\DashboardModule;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Changes an account's name, email, role or assigned modules; every field is
 * optional. The NIP identifies the account and cannot change.
 */
class UpdateAccountRequest extends FormRequest
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
            'nama' => ['sometimes', 'required', 'string', 'max:255'],
            'surel' => ['sometimes', 'nullable', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($this->account())],
            'peran' => ['sometimes', 'required', Rule::enum(UserRole::class)],
            'modul' => ['sometimes', 'list'],
            'modul.*' => ['string', Rule::enum(DashboardModule::class)->only(DashboardModule::assignable())],
        ];
    }

    /**
     * Administrators cannot change their own role, so they cannot lock
     * themselves out of account management.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $role = $this->input('peran');

                if ($validator->errors()->has('peran') || $role === null || ! $this->user()->is($this->account())) {
                    return;
                }

                if ($role !== $this->account()->role->value) {
                    $validator->errors()->add('peran', 'Anda tidak dapat mengubah peran akun Anda sendiri.');
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
     * The validated changes as User attributes. Modules are stored once
     * each, in the order of the DashboardModule cases.
     *
     * @return array<string, mixed>
     */
    public function accountAttributes(): array
    {
        $attributes = ['nama' => 'name', 'surel' => 'email', 'peran' => 'role', 'modul' => 'modules'];

        return collect($this->validated())
            ->mapWithKeys(fn (mixed $value, string $field): array => [
                $attributes[$field] => $field === 'modul' ? DashboardModule::ordered($value) : $value,
            ])
            ->all();
    }

    private function account(): User
    {
        return $this->route('user');
    }
}
