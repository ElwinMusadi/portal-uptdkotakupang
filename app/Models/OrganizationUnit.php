<?php

namespace App\Models;

use Database\Factories\OrganizationUnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A unit on the portal's Struktur Organisasi page (D-033): the head of the
 * UPTD, its subdivision and sections, the functional group, or a partner
 * in the joint office. "induk" is the unit it reports to; partners report
 * to none. Changes reach the portal when saved; there is no workflow. The
 * official's NIP stays internal.
 *
 * @property list<string> $anggota
 */
#[Fillable(['nama', 'singkatan', 'jenis', 'induk', 'jabatan', 'pejabat', 'nip', 'foto', 'anggota', 'uraian_tugas', 'catatan'])]
class OrganizationUnit extends Model
{
    /** @use HasFactory<OrganizationUnitFactory> */
    use HasFactory, HasUlids;

    /**
     * Kinds of unit, as the dashboard names them.
     *
     * @var list<string>
     */
    public const array KINDS = ['pimpinan', 'tata-usaha', 'seksi', 'fungsional', 'mitra'];

    /**
     * A partner in the joint office: coordination, not command.
     */
    public const string PARTNER = 'mitra';

    /**
     * A new unit's optional texts are empty.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'jabatan' => '',
        'pejabat' => '',
        'nip' => '',
        'uraian_tugas' => '',
        'catatan' => '',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'anggota' => 'array',
            'urutan' => 'integer',
        ];
    }
}
