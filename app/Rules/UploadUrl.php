<?php

namespace App\Rules;

use App\Enums\UploadTarget;
use App\Models\Upload;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * The address of a file uploaded to PINTU for the given target (D-028),
 * exactly as the upload returned it. Other addresses, such as images on
 * other sites or files uploaded for another target, are rejected.
 */
class UploadUrl implements ValidationRule
{
    /**
     * @param  string  $message  The message for any other address.
     */
    public function __construct(private UploadTarget $target, private string $message) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || Upload::fromUrl($value, $this->target) === null) {
            $fail($this->message);
        }
    }
}
