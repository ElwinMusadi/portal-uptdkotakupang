<?php

namespace App\Http\Resources;

use App\Models\StaticPage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A static page as the dashboard edits it, in the shape of the dashboard's
 * Halaman model. A page that was never saved shows when it was added. The
 * public portal uses PublicStaticPageController instead.
 *
 * @mixin StaticPage
 */
class StaticPageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{id: string, slug: string, judul: string, judulLanjutan: string, pengantar: string, isi: string, urutan: int, dibuat: ?string, diperbarui: ?string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'judul' => $this->judul,
            'judulLanjutan' => $this->judul_lanjutan,
            'pengantar' => $this->pengantar,
            'isi' => $this->isi,
            'urutan' => $this->urutan,
            'dibuat' => $this->created_at?->toIso8601String(),
            'diperbarui' => ($this->updated_at ?? $this->created_at)?->toIso8601String(),
        ];
    }
}
