<?php

namespace App\Http\Resources;

use App\Models\QueueDisplay;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A waiting-room TV display as administrators list it (D-051); never its
 * token.
 *
 * @mixin QueueDisplay
 */
class QueueDisplayResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{id: string, nama: string, terakhirTerlihat: ?string, dibuat: ?string, dibuatOleh: ?string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'terakhirTerlihat' => $this->terakhir_terlihat?->toIso8601String(),
            'dibuat' => $this->created_at?->toIso8601String(),
            'dibuatOleh' => $this->creator?->name,
        ];
    }
}
