<?php

namespace App\Http\Resources;

use App\Models\QueueEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One line of a queue ticket's history in the archive (D-051 slice 4): what
 * happened, at which stage and counter, by whom, and whether the system did
 * it on its own.
 *
 * @mixin QueueEvent
 */
class QueueEventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{aksi: string, tahap: ?string, keterangan: ?string, loket: ?string, petugas: ?string, otomatis: bool, waktu: ?string}
     */
    public function toArray(Request $request): array
    {
        return [
            'aksi' => $this->aksi,
            'tahap' => $this->tahap?->value,
            'keterangan' => $this->keterangan,
            'loket' => $this->counter?->nama,
            'petugas' => $this->user?->name,
            'otomatis' => $this->otomatis,
            'waktu' => $this->created_at?->toIso8601String(),
        ];
    }
}
