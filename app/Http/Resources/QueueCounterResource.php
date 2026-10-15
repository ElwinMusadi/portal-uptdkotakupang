<?php

namespace App\Http\Resources;

use App\Models\QueueCounter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A counter of Antrean Online as the dashboard lists it (D-051).
 *
 * @mixin QueueCounter
 */
class QueueCounterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{id: string, nama: string, tahap: string, layanan: list<string>, namaSuara: string, aktif: bool}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'tahap' => $this->tahap->value,
            'layanan' => $this->layanan,
            'namaSuara' => $this->nama_suara,
            'aktif' => $this->aktif,
        ];
    }
}
