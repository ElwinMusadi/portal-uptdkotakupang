<?php

namespace App\Models;

use Database\Factories\StaticPageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A long-text page of the portal, kept in Halaman Statis (D-024). The portal
 * has a page for each slug in PAGES, so pages are edited but not added or
 * deleted, and a slug never changes. "isi" is the HTML the dashboard's
 * editor writes, limited to the elements RichText allows. "updated_at" stays
 * empty until the page is first saved; until then the portal keeps the text
 * in its markup.
 */
#[Fillable(['judul', 'judul_lanjutan', 'pengantar', 'isi'])]
class StaticPage extends Model
{
    /** @use HasFactory<StaticPageFactory> */
    use HasFactory, HasUlids;

    /**
     * The portal's static pages by slug, with the name the activity log uses.
     *
     * @var array<string, string>
     */
    public const array PAGES = [
        'kebijakan-privasi' => 'Kebijakan Privasi',
        'syarat-ketentuan' => 'Syarat & Ketentuan',
    ];

    /**
     * Longest title and muted continuation, in characters. Matches the dashboard form.
     */
    public const int MAX_TITLE_LENGTH = 80;

    /**
     * Longest small text above the title, in characters. Matches the dashboard form.
     */
    public const int MAX_INTRO_LENGTH = 120;

    /**
     * Longest content, in characters of HTML. Matches the dashboard form.
     */
    public const int MAX_CONTENT_LENGTH = 100000;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
        ];
    }

    /**
     * The page's name, e.g. "Kebijakan Privasi".
     */
    public function name(): string
    {
        return self::PAGES[$this->slug] ?? $this->judul;
    }
}
