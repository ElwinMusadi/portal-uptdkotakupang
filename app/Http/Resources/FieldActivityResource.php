<?php

namespace App\Http\Resources;

use App\Models\FieldActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A field activity as administrators see it in the dashboard: its place,
 * radius and officers, how many have clocked in, and, when loaded, the
 * clock-ins themselves.
 *
 * @mixin FieldActivity
 */
class FieldActivityResource extends JsonResource
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
            'nama' => $this->nama,
            'tanggal' => $this->tanggal->toDateString(),
            'tempat' => $this->tempat,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'radius' => $this->radius,
            'catatan' => $this->catatan,
            'petugas' => $this->officers->sortBy('name')->map(fn (User $officer): array => [
                'id' => $officer->id,
                'nama' => $officer->name,
                'nip' => $officer->nip,
            ])->values()->all(),
            'jumlahHadir' => $this->attendances_count,
            'absen' => FieldAttendanceResource::collection($this->whenLoaded('attendances')),
            'dibuat' => $this->created_at?->toIso8601String(),
            'diperbarui' => $this->updated_at?->toIso8601String(),
        ];
    }
}
