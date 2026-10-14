<?php

namespace App\Http\Resources;

use App\Models\FieldAttendance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An officer's clock-in as administrators see it. The selfie's address is
 * the application's private file route, readable by the officer and
 * administrators only.
 *
 * @mixin FieldAttendance
 */
class FieldAttendanceResource extends JsonResource
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
            'petugas' => ['id' => $this->user->id, 'nama' => $this->user->name, 'nip' => $this->user->nip],
            'waktu' => $this->waktu->toIso8601String(),
            'lat' => $this->lat,
            'lng' => $this->lng,
            'akurasi' => $this->akurasi,
            'jarak' => $this->jarak,
            'foto' => $this->foto,
        ];
    }
}
