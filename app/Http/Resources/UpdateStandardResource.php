<?php

namespace App\Http\Resources;

use App\Models\UpdateStandard;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An update standard in the shape of the dashboard's Pemutakhiran model.
 *
 * @mixin UpdateStandard
 */
class UpdateStandardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{id: string, konten: string, modul: string, unit: string, frekuensi: string, intervalHari: int, urutan: int, dibuat: ?string, diperbarui: ?string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'konten' => $this->konten,
            'modul' => $this->modul->value,
            'unit' => $this->unit,
            'frekuensi' => $this->frekuensi,
            'intervalHari' => $this->interval_hari,
            'urutan' => $this->urutan,
            'dibuat' => $this->created_at?->toIso8601String(),
            'diperbarui' => $this->updated_at?->toIso8601String(),
        ];
    }
}
