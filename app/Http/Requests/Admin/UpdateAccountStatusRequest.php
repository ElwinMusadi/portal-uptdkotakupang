<?php

namespace App\Http\Requests\Admin;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAccountStatusRequest extends FormRequest
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
     * Get the validation rules that apply to the request. Pending is not
     * accepted: it is reached by issuing an activation link.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(AccountStatus::class)->except([AccountStatus::Pending])],
        ];
    }

    /**
     * Only the transitions AccountStatus allows.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('status')) {
                    return;
                }

                $current = $this->account()->status;
                $target = $this->newStatus();

                if (! $current->canChangeTo($target)) {
                    $validator->errors()->add('status', $this->refusal($current, $target));
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
            'status.required' => 'Pilih status akun.',
            'status.enum' => 'Status tidak valid.',
        ];
    }

    public function newStatus(): AccountStatus
    {
        return $this->enum('status', AccountStatus::class);
    }

    private function account(): User
    {
        return $this->route('user');
    }

    private function refusal(AccountStatus $current, AccountStatus $target): string
    {
        if ($current === $target) {
            return match ($target) {
                AccountStatus::Active => 'Akun ini sudah aktif.',
                AccountStatus::Suspended => 'Akun ini sudah ditangguhkan.',
                default => 'Akun ini sudah dinonaktifkan.',
            };
        }

        return $target === AccountStatus::Suspended
            ? 'Hanya akun aktif yang bisa ditangguhkan.'
            : 'Hanya akun yang ditangguhkan yang bisa diaktifkan kembali. Akun lain aktif setelah pemiliknya memakai tautan aktivasi.';
    }
}
