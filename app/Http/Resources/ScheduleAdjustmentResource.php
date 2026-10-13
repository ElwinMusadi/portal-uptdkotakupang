<?php

namespace App\Http\Resources;

use App\Models\ScheduleAdjustment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A schedule adjustment in the shape of the dashboard's PenyesuaianJadwal
 * model.
 *
 * @mixin ScheduleAdjustment
 */
class ScheduleAdjustmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{id: string, tanggal: string, jenis: string, mulai: string, selesai: string, keterangan: string, urutan: int, dibuat: ?string, diperbarui: ?string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tanggal' => $this->tanggal,
            'jenis' => $this->jenis,
            'mulai' => $this->mulai,
            'selesai' => $this->selesai,
            'keterangan' => $this->keterangan,
            'urutan' => $this->urutan,
            'dibuat' => $this->created_at?->toIso8601String(),
            'diperbarui' => $this->updated_at?->toIso8601String(),
        ];
    }
}
