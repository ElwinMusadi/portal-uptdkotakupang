<?php

namespace App\Http\Resources;

use App\Models\Message;
use App\Models\MessageResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A message in the shape of the dashboard's Pesan model, with its responses
 * credited to the employees who recorded them.
 *
 * @mixin Message
 */
class MessageResource extends JsonResource
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
            'tiket' => $this->tiket,
            'jenis' => $this->jenis,
            'nama' => $this->nama,
            'kontak' => $this->kontak,
            'layanan' => $this->layanan,
            'isi' => $this->isi,
            'sumber' => $this->sumber,
            'status' => $this->status,
            'diterima' => $this->diterima->toIso8601String(),
            'ditangani' => $this->ditangani,
            'tanggapan' => $this->responses->map(fn (MessageResponse $response): array => [
                'id' => $response->id,
                'isi' => $response->isi,
                'oleh' => $response->user->name,
                'waktu' => $response->created_at->toIso8601String(),
            ])->values()->all(),
            'urutan' => $this->urutan,
            'dibuat' => $this->created_at?->toIso8601String(),
            'diperbarui' => $this->updated_at?->toIso8601String(),
        ];
    }
}
