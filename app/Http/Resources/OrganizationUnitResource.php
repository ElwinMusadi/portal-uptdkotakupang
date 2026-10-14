<?php

namespace App\Http\Resources;

use App\Models\OrganizationUnit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A unit as the dashboard edits it, in the shape of the dashboard's
 * UnitKerja model. The public portal uses PublicOrganizationController,
 * which leaves out the NIP.
 *
 * @mixin OrganizationUnit
 */
class OrganizationUnitResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'singkatan' => $this->singkatan,
            'jenis' => $this->jenis,
            'induk' => $this->induk,
            'jabatan' => $this->jabatan,
            'pejabat' => $this->pejabat,
            'nip' => $this->nip,
            'foto' => $this->foto,
            'anggota' => $this->anggota,
            'uraianTugas' => $this->uraian_tugas,
            'catatan' => $this->catatan,
            'urutan' => $this->urutan,
            'dibuat' => $this->created_at?->toIso8601String(),
            'diperbarui' => $this->updated_at?->toIso8601String(),
        ];
    }
}
