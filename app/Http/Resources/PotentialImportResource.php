<?php

namespace App\Http\Resources;

use App\Models\PotentialImport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An import of the potential-tax file as the dashboard shows it (D-052).
 *
 * @mixin PotentialImport
 */
class PotentialImportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{id: string, berkas: string, jumlah: int, diterima: int, tanggalPenetapan: ?string, diimporOleh: ?string, dimulai: ?string, selesai: ?string, tampil: bool}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'berkas' => $this->berkas,
            'jumlah' => $this->jumlah,
            'diterima' => $this->diterima,
            'tanggalPenetapan' => $this->tanggal_penetapan?->toDateString(),
            'diimporOleh' => $this->importer?->name,
            'dimulai' => $this->created_at?->toIso8601String(),
            'selesai' => $this->selesai_pada?->toIso8601String(),
            'tampil' => $this->tampil,
        ];
    }
}
