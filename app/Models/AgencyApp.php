<?php

namespace App\Models;

use Database\Factories\AgencyAppFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An app or website made by BPAD or the UPTD, on the portal's Aplikasi &
 * Situs page (D-054): a website, or an Android app on Google Play. Changes
 * reach the portal when saved; there is no workflow. The logo is an upload
 * for Aplikasi (D-028) or one of the logos that came with the first list,
 * kept in public/assets/img/aplikasi.
 */
#[Fillable(['nama', 'nama_lengkap', 'keterangan', 'jenis', 'pengelola', 'tautan', 'logo'])]
class AgencyApp extends Model
{
    /** @use HasFactory<AgencyAppFactory> */
    use HasFactory, HasUlids;

    /**
     * Kinds of app: a website, or an Android app on Google Play.
     *
     * @var list<string>
     */
    public const array KINDS = ['situs', 'android'];

    /**
     * Who made and runs the app: BPAD Provinsi NTT or the UPTD.
     *
     * @var list<string>
     */
    public const array OWNERS = ['bpad', 'uptd'];

    /**
     * A new app's optional texts are empty.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'nama_lengkap' => '',
        'keterangan' => '',
    ];

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
     * The logo's full address: a logo of the first list on this site
     * (stored as a path), or the upload's, which asset() leaves as it is.
     */
    public function logoUrl(): ?string
    {
        return $this->logo === null ? null : asset($this->logo);
    }
}
