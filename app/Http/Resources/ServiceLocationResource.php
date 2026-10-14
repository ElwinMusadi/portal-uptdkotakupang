<?php

namespace App\Http\Resources;

use App\Models\ServiceLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A place in the list of places, in the shape of the dashboard's
 * LokasiLayanan model (D-044).
 *
 * @mixin ServiceLocation
 */
class ServiceLocationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{id: string, nama: string, kecamatan: string, urutan: int, dibuat: ?string, diperbarui: ?string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'kecamatan' => $this->kecamatan,
            'urutan' => $this->urutan,
            'dibuat' => $this->created_at?->toIso8601String(),
            'diperbarui' => $this->updated_at?->toIso8601String(),
        ];
    }
}
