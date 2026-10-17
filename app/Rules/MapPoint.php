<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * A point on the map as latitude and longitude in decimal degrees, as Google
 * Maps copies it, e.g. "-10.172878287411304, 123.60848636621598" (D-063).
 */
class MapPoint implements ValidationRule
{
    private const string PATTERN = '/^\s*(-?\d{1,2}(?:\.\d+)?)\s*,\s*(-?\d{1,3}(?:\.\d+)?)\s*\z/';

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

        if (preg_match(self::PATTERN, $value, $parts) !== 1 || abs((float) $parts[1]) > 90 || abs((float) $parts[2]) > 180) {
            $fail('Tulis titik peta sebagai lintang, bujur, mis. -10.172878, 123.608486.');
        }
    }

    /**
     * A valid point written the one way the portal reads it, e.g.
     * "-10.1728 ,123.6084" as "-10.1728, 123.6084".
     */
    public static function normalize(string $value): string
    {
        preg_match(self::PATTERN, $value, $parts);

        return "{$parts[1]}, {$parts[2]}";
    }
}
