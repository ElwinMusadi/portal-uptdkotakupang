<?php

namespace App\Http\Resources;

use App\Models\Documentation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An activity as the dashboard edits it, in the shape of the dashboard's
 * Dokumentasi model. The public portal uses PublicDocumentationController
 * instead.
 *
 * @mixin Documentation
 */
class DocumentationResource extends JsonResource
{
    /**
     * Transform the resource into an array. Activities are listed by date,
     * so their order is always 0.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'judul' => $this->judul,
            'kategori' => $this->kategori,
            'lokasi' => $this->lokasi,
            'tanggal' => $this->tanggal->toDateString(),
            'deskripsi' => $this->deskripsi,
            'foto' => $this->foto,
            'status' => $this->status->value,
            'penyedia' => $this->penyedia,
            'pemeriksa' => $this->pemeriksa,
            'catatanRevisi' => $this->catatan_revisi,
            'terbitPada' => $this->terbit_pada?->toIso8601String(),
            'riwayat' => $this->riwayat,
            'urutan' => 0,
            'dibuat' => $this->created_at?->toIso8601String(),
            'diperbarui' => $this->updated_at?->toIso8601String(),
        ];
    }
}
