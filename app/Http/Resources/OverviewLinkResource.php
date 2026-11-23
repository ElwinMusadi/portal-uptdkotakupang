<?php

namespace App\Http\Resources;

use App\Models\OverviewLink;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A link to the Ikhtisar page as administrators list it (D-099); never its
 * token.
 *
 * @mixin OverviewLink
 */
class OverviewLinkResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{id: string, nama: string, terakhirDibuka: ?string, dibuat: ?string, dibuatOleh: ?string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'terakhirDibuka' => $this->terakhir_dibuka?->toIso8601String(),
            'dibuat' => $this->created_at?->toIso8601String(),
            'dibuatOleh' => $this->creator?->name,
        ];
    }
}
