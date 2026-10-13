<?php

namespace App\Http\Requests\Dashboard;

use App\Models\User;
use App\Rules\PlainText;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The signed-in employee's profile in the dashboard's Akun tab (D-027): job
 * title, unit and role in the information workflow. The name and email
 * belong to the account, which only administrators change, so they may be
 * sent as they are but never changed here.
 */
class UpdateOwnAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request: every
     * signed-in employee edits their own profile.
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
        /** @var User $user */
        $user = $this->user();
        $unchanged = fn (?string $current, string $message): Closure => function (string $attribute, mixed $value, Closure $fail) use ($current, $message): void {
            if ($value !== $current) {
                $fail($message);
            }
        };

        return [
            'nama' => ['sometimes', $unchanged($user->name, 'Nama mengikuti akun dan hanya dapat diubah Administrator di Akun Pegawai.')],
            'email' => ['sometimes', $unchanged($user->email, 'Surel mengikuti akun dan hanya dapat diubah Administrator di Akun Pegawai.')],
            'jabatan' => ['nullable', 'string', 'max:120', new PlainText('Jabatan')],
            'unit' => ['nullable', 'string', 'max:120', new PlainText('Unit')],
            'peran' => ['required', 'string', Rule::in(User::INFORMATION_ROLES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'jabatan.string' => 'Jabatan tidak valid.',
            'jabatan.max' => 'Jabatan maksimal :max karakter.',
            'unit.string' => 'Unit tidak valid.',
            'unit.max' => 'Unit maksimal :max karakter.',
            'peran.required' => 'Pilih peran dalam pengelolaan informasi.',
            'peran.string' => 'Peran dalam pengelolaan informasi tidak valid.',
            'peran.in' => 'Peran dalam pengelolaan informasi tidak dikenal.',
        ];
    }

    /**
     * The profile by column; an emptied job title or unit is stored as "".
     *
     * @return array{jabatan: string, unit: string, peran_informasi: string}
     */
    public function profile(): array
    {
        return [
            'jabatan' => $this->validated('jabatan') ?? '',
            'unit' => $this->validated('unit') ?? '',
            'peran_informasi' => $this->validated('peran'),
        ];
    }
}
