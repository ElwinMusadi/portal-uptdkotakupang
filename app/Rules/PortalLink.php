<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * A link the portal may put in an href: a full http(s) address, mailto:,
 * tel:, an anchor on the page, or a portal page such as "jadwal.html" or
 * "kontak.html#masukan". Other schemes, such as javascript:, and whitespace
 * are rejected.
 */
class PortalLink implements ValidationRule
{
    public function __construct(private string $label) {}

    /**
     * Whether the portal may put the value in an href.
     */
    public static function allows(string $value): bool
    {
        return preg_match('/^(https?:\/\/[^\/\s]+|mailto:\S|tel:\+?[0-9]|#|\.{1,2}\/|[\w-]+\.html(?:[#?]|\z))/i', $value) === 1
            && preg_match('/[\s\x00-\x1F\x7F]/', $value) !== 1;
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

        if (! self::allows($value)) {
            $fail("{$this->label}: gunakan alamat lengkap (https://…) atau nama halaman portal (mis. jadwal.html).");
        }
    }
}
