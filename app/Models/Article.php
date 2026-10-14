<?php

namespace App\Models;

use App\Models\Concerns\HasPublicationWorkflow;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A news item or announcement on the portal's Informasi page, read at
 * /berita/{slug} (Berita & Pengumuman, D-031). It goes through the
 * publication workflow; only published articles reach the portal. The
 * cover photo is the address of a PINTU upload for "berita".
 *
 * @property Carbon $tanggal
 */
#[Fillable(['judul', 'slug', 'kategori', 'ringkasan', 'isi', 'sampul', 'keterangan_sampul', 'kredit_foto', 'tanggal', 'sorotan', 'pengumuman_beranda', 'penyedia', 'pemeriksa'])]
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory, HasPublicationWorkflow, HasUlids;

    /**
     * Categories as the portal's filter shows them.
     *
     * @var list<string>
     */
    public const array CATEGORIES = ['berita', 'pengumuman', 'keliling', 'edukasi'];

    /**
     * A slug: lowercase letters and digits joined by single hyphens.
     */
    public const string SLUG_PATTERN = '[a-z0-9]+(?:-[a-z0-9]+)*';

    /**
     * Longest body, in characters of HTML. Matches the static pages.
     */
    public const int MAX_CONTENT_LENGTH = 100000;

    /**
     * A new article's optional texts are empty, as in the table.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'keterangan_sampul' => '',
        'kredit_foto' => '',
        'sorotan' => false,
        'pengumuman_beranda' => false,
        'pemeriksa' => '',
        'catatan_revisi' => '',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'sorotan' => 'boolean',
            'pengumuman_beranda' => 'boolean',
        ];
    }

    /**
     * Minutes a reader needs for the body, at 200 words a minute and at
     * least one, as the dashboard estimates it.
     */
    public function readingMinutes(): int
    {
        // Every tag separates words, so "<p>a</p><p>b</p>" counts two.
        $words = preg_split('/\s+/', strip_tags(str_replace('<', ' <', $this->isi)), -1, PREG_SPLIT_NO_EMPTY);

        return max(1, (int) round(count($words) / 200));
    }
}
