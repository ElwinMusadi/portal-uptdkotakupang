<?php

namespace App\Http\Resources;

use App\Models\AgencyApp;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An app as the dashboard edits it, in the shape of the dashboard's
 * Aplikasi model. The logo is a full address, also for the logos that came
 * with the first list.
 *
 * @mixin AgencyApp
 */
class AgencyAppResource extends JsonResource
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
            'namaLengkap' => $this->nama_lengkap,
            'keterangan' => $this->keterangan,
            'jenis' => $this->jenis,
            'pengelola' => $this->pengelola,
            'tautan' => $this->tautan,
            'logo' => $this->logoUrl(),
            'urutan' => $this->urutan,
            'dibuat' => $this->created_at?->toIso8601String(),
            'diperbarui' => $this->updated_at?->toIso8601String(),
        ];
    }
}
