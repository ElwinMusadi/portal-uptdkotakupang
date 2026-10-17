<?php

namespace App\Enums;

/**
 * The typefaces a theme may use (D-057): the device's system font, or one of
 * the curated OFL fonts hosted with the site in public/assets/fonts, each a
 * Latin variable-weight WOFF2 file with its licence.
 */
enum ThemeFont: string
{
    case Sistem = 'sistem';
    case Inter = 'inter';
    case PlusJakartaSans = 'plus-jakarta-sans';
    case DmSans = 'dm-sans';
    case SourceSans3 = 'source-sans-3';
    case IbmPlexSans = 'ibm-plex-sans';
    case NunitoSans = 'nunito-sans';
    /** Headings only */
    case Manrope = 'manrope';
    /** Headings only */
    case Lexend = 'lexend';

    /**
     * The font family's name, or null for the system font.
     */
    public function family(): ?string
    {
        return match ($this) {
            self::Sistem => null,
            self::Inter => 'Inter',
            self::PlusJakartaSans => 'Plus Jakarta Sans',
            self::DmSans => 'DM Sans',
            self::SourceSans3 => 'Source Sans 3',
            self::IbmPlexSans => 'IBM Plex Sans',
            self::NunitoSans => 'Nunito Sans',
            self::Manrope => 'Manrope',
            self::Lexend => 'Lexend',
        };
    }

    /**
     * The range of the font file's weight axis, as @font-face states it.
     */
    public function weights(): string
    {
        return match ($this) {
            self::Sistem, self::Inter, self::Lexend => '100 900',
            self::PlusJakartaSans, self::Manrope => '200 800',
            self::DmSans => '100 1000',
            self::SourceSans3 => '200 900',
            self::IbmPlexSans => '100 700',
            self::NunitoSans => '200 1000',
        };
    }

    /**
     * The font file's address from the site root, or null for the system font.
     */
    public function file(): ?string
    {
        return $this === self::Sistem ? null : "/assets/fonts/{$this->value}/{$this->value}-latin-wght-normal.woff2";
    }

    /**
     * Whether the font may set body text; the others are for headings only.
     */
    public function forText(): bool
    {
        return ! in_array($this, [self::Manrope, self::Lexend], true);
    }

    /**
     * The fonts a theme may use for body text.
     *
     * @return list<self>
     */
    public static function textFonts(): array
    {
        return array_values(array_filter(self::cases(), fn (self $font): bool => $font->forText()));
    }
}
