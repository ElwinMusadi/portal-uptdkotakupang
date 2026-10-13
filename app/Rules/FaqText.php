<?php

namespace App\Rules;

use App\Models\Faq;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * A FAQ question or answer the public portal can show (GC18-04): no control
 * characters other than tab and line breaks, and something visible. Presence
 * and length are checked by their own rules.
 */
class FaqText implements ValidationRule
{
    /**
     * @param  string  $label  The field's name in messages, e.g. "Pertanyaan".
     */
    public function __construct(private string $label) {}

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

        if (Faq::hasForbiddenControlCharacter($value)) {
            $fail("{$this->label} berisi karakter kontrol yang tidak diizinkan.");
        } elseif (! Faq::hasVisibleText($value)) {
            $fail("{$this->label} tidak boleh hanya berisi spasi atau karakter tak terlihat.");
        }
    }
}
