<?php

namespace App\Http\Resources;

use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A FAQ entry as the dashboard edits it, in the shape of the dashboard's
 * Faq model. The public portal uses PublicFaqController instead.
 *
 * @mixin Faq
 */
class FaqResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{id: string, pertanyaan: string, jawaban: string, status: string, publik: bool, tampil: bool, urutan: int, dibuat: ?string, diperbarui: ?string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pertanyaan' => $this->pertanyaan,
            'jawaban' => $this->jawaban,
            'status' => $this->status->value,
            'publik' => $this->publik,
            'tampil' => $this->tampil,
            'urutan' => $this->urutan,
            'dibuat' => $this->created_at?->toIso8601String(),
            'diperbarui' => $this->updated_at?->toIso8601String(),
        ];
    }
}
