<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * A portal theme an administrator previews on the portal before saving it
 * (D-057). The unsaved portal part waits in the cache under a random token;
 * the preview link puts the token in a cookie, and /tema-portal.css serves
 * that browser the unsaved theme until the link expires.
 *
 * @phpstan-import-type ThemeSection from ThemeVariables
 */
final class ThemePreview
{
    /**
     * The cookie holding the token in the previewing browser.
     */
    public const string COOKIE = 'pintu_pratinjau_tema';

    /**
     * How long a preview link and its cookie last.
     */
    public const int MINUTES = 30;

    /**
     * Keep an unsaved portal theme for a preview and give its token.
     *
     * @param  ThemeSection  $section
     */
    public static function store(array $section): string
    {
        $token = Str::random(40);
        Cache::put(self::key($token), $section, now()->addMinutes(self::MINUTES));

        return $token;
    }

    /**
     * The unsaved portal theme of a token, or null when it is unknown or
     * expired. The token comes from the encrypted cookie the preview link
     * set, or from the link itself.
     *
     * @return ThemeSection|null
     */
    public static function find(?string $token): ?array
    {
        return $token === null ? null : Cache::get(self::key($token));
    }

    private static function key(string $token): string
    {
        return "tema-pratinjau:{$token}";
    }
}
