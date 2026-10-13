<?php

namespace App\Http\Resources;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A document as the dashboard edits it, in the shape of the dashboard's
 * Dokumen model. The public portal uses PublicDocumentController instead.
 *
 * @mixin Document
 */
class DocumentResource extends JsonResource
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
            'judul' => $this->judul,
            'keterangan' => $this->keterangan,
            'kategori' => $this->kategori,
            'format' => $this->format,
            'nomor' => $this->nomor,
            'tahun' => $this->tahun,
            'berkas' => $this->berkas,
            'tautan' => $this->tautan,
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
