<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Validation\Rules\Password;

/**
 * The account password policy (Password::defaults()) with Indonesian
 * messages, shared by the activation page and the admin command.
 */
class StrongPassword implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $message = Validator::make(['sandi' => $value], [
            'sandi' => ['string', 'not_regex:/\x00/', Password::defaults()],
        ], [
            'sandi.string' => 'Kata sandi tidak valid.',
            'sandi.not_regex' => 'Kata sandi tidak valid.',
            'sandi.min' => 'Kata sandi minimal :min karakter.',
            'sandi.password.letters' => 'Kata sandi harus berisi huruf.',
            'sandi.password.numbers' => 'Kata sandi harus berisi angka.',
        ])->errors()->first('sandi');

        if ($message !== '') {
            $fail($message);
        }
    }
}
