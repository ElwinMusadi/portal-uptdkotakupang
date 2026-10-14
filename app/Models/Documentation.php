<?php

namespace App\Models;

use App\Models\Concerns\HasPublicationWorkflow;
use Database\Factories\DocumentationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * An activity and its photos on the portal's Dokumentasi Kegiatan page
 * (D-032). It goes through the publication workflow; only published
 * activities reach the portal, and only with at least one photo. Every
 * photo is the address of a PINTU upload for "dokumentasi".
 *
 * @property Carbon $tanggal
 * @property list<array{id: string, src: string, keterangan: string}> $foto
 */
#[Fillable(['judul', 'kategori', 'lokasi', 'tanggal', 'deskripsi', 'foto', 'penyedia', 'pemeriksa'])]
class Documentation extends Model
{
    /** @use HasFactory<DocumentationFactory> */
    use HasFactory, HasPublicationWorkflow, HasUlids;

    /**
     * Categories as the portal's filter shows them.
     *
     * @var list<string>
     */
    public const array CATEGORIES = ['keliling', 'sosialisasi', 'operasi', 'internal'];

    /**
     * Most photos for one activity.
     */
    public const int MAX_PHOTOS = 30;

    /**
     * A new activity's optional texts are empty and it has no photos yet.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'deskripsi' => '',
        'foto' => '[]',
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
            'foto' => 'array',
        ];
    }

    /**
     * The addresses of the activity's photos.
     *
     * @return list<string>
     */
    public function photoUrls(): array
    {
        return array_column($this->foto, 'src');
    }
}
