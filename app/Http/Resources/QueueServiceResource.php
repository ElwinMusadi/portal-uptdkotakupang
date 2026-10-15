<?php

namespace App\Http\Resources;

use App\Models\QueueService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A queue service as the dashboard lists it (D-051).
 *
 * @mixin QueueService
 */
class QueueServiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{id: string, kode: string, nama: string, cakupan: string, tahap: list<string>, aktif: bool}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kode' => $this->kode,
            'nama' => $this->nama,
            'cakupan' => $this->cakupan,
            'tahap' => $this->tahap,
            'aktif' => $this->aktif,
        ];
    }
}
