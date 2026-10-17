<?php

namespace App\Http\Resources;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A service as the dashboard edits it, in the shape of the dashboard's
 * Layanan model. The public portal uses PublicServiceController instead.
 *
 * @mixin Service
 */
class ServiceResource extends JsonResource
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
            'slug' => $this->slug,
            'ringkasan' => $this->ringkasan,
            'loket' => $this->loket,
            'jenis' => $this->jenis,
            'cekFisik' => $this->cek_fisik,
            'nonTunai' => $this->non_tunai,
            'diwakilkan' => $this->diwakilkan,
            'label' => $this->label,
            'persyaratan' => $this->persyaratan,
            'situasi' => $this->situasi,
            'catatan' => $this->catatan,
            'unggulan' => $this->unggulan,
            'status' => $this->status->value,
            'penyedia' => $this->penyedia,
            'pemeriksa' => $this->pemeriksa,
            'catatanRevisi' => $this->catatan_revisi,
            'terbitPada' => $this->terbit_pada?->toIso8601String(),
            'riwayat' => $this->riwayat,
            'urutan' => $this->urutan,
            'dibuat' => $this->created_at?->toIso8601String(),
            'diperbarui' => $this->updated_at?->toIso8601String(),
        ];
    }
}
