<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * A YouTube video link as people copy it: a share link (youtu.be/…), a
 * watch, Shorts, live or embed link, with or without tracking parameters
 * such as "si" (D-040).
 */
class YoutubeUrl implements ValidationRule
{
    public function __construct(private string $label) {}

    /**
     * The video's ID, or null when the value is not a YouTube video link.
     */
    public static function id(string $url): ?string
    {
        $parts = parse_url(trim($url));

        if (! is_array($parts) || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            return null;
        }

        $host = preg_replace('/^(www|m)\./', '', strtolower($parts['host'] ?? ''));
        $path = $parts['path'] ?? '';
        parse_str($parts['query'] ?? '', $query);

        $id = match (true) {
            $host === 'youtu.be' => substr($path, 1),
            ! in_array($host, ['youtube.com', 'youtube-nocookie.com'], true) => null,
            $path === '/watch' => $query['v'] ?? null,
            default => preg_match('#^/(?:embed|shorts|live)/([^/]+)/?$#', $path, $match) === 1 ? $match[1] : null,
        };

        return is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/', $id) === 1 ? $id : null;
    }

    /**
     * The link as it is stored: the plain watch address, without tracking
     * parameters.
     */
    public static function canonical(string $url): ?string
    {
        $id = self::id($url);

        return $id === null ? null : "https://www.youtube.com/watch?v={$id}";
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && self::id($value) === null) {
            $fail("{$this->label}: gunakan tautan video YouTube, mis. https://youtu.be/… atau https://www.youtube.com/watch?v=….");
        }
    }
}
