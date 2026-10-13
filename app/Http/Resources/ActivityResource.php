<?php

namespace App\Http\Resources;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An activity log entry in the shape of the dashboard's Aktivitas model.
 *
 * @mixin Activity
 */
class ActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{id: string, waktu: ?string, oleh: string, aksi: string, modul: string, target: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'waktu' => $this->created_at?->toIso8601String(),
            'oleh' => $this->user->name,
            'aksi' => $this->action,
            'modul' => $this->module->label(),
            'target' => $this->target,
        ];
    }
}
