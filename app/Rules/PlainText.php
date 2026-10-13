<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Text the public portal shows as plain text (GC18-04 for FAQ entries): no
 * control characters other than tab and line breaks, and something visible.
 * Presence and length are checked by their own rules.
 */
class PlainText implements ValidationRule
{
    /**
     * @param  string  $label  The field's name in messages, e.g. "Pertanyaan".
     */
    public function __construct(private string $label) {}

    /**
     * Whether the text holds a control character other than tab and line
     * breaks. Text that is not valid UTF-8 counts as holding one.
     */
    public static function hasForbiddenControlCharacter(string $text): bool
    {
        return preg_match('/[\x{00}-\x{08}\x{0B}\x{0C}\x{0E}-\x{1F}\x{7F}-\x{9F}]/u', $text) !== 0;
    }

    /**
     * Whether the text shows something: not only spaces, format or
     * private-use characters, combining marks or blank fillers.
     */
    public static function hasVisibleText(string $text): bool
    {
        return preg_match('/[^\p{Z}\p{C}\p{M}\x{115F}\x{1160}\x{2800}\x{3164}\x{FFA0}]/u', $text) === 1;
    }

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

        if (self::hasForbiddenControlCharacter($value)) {
            $fail("{$this->label} berisi karakter kontrol yang tidak diizinkan.");
        } elseif (! self::hasVisibleText($value)) {
            $fail("{$this->label} tidak boleh hanya berisi spasi atau karakter tak terlihat.");
        }
    }
}
