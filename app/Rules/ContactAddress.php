<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * An email address or a WhatsApp/phone number staff can reply to, e.g.
 * "warga@example.com" or "+62 812-3456-7890".
 */
class ContactAddress implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $isEmail = filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
        $isPhone = preg_match('/^\+?[0-9][0-9 .\-]{6,20}[0-9]$/', $value) === 1
            && preg_match_all('/[0-9]/', $value) >= 9;

        if (! $isEmail && ! $isPhone) {
            $fail('Isi email atau nomor WhatsApp yang valid.');
        }
    }
}
