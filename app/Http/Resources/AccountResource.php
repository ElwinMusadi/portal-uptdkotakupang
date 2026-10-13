<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An account as administrators see it in the dashboard. Passwords and
 * remember tokens never leave the server.
 *
 * @mixin User
 */
class AccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{id: string, nip: string, nama: string, surel: ?string, peran: string, status: string, modul: list<string>, dibuat: ?string, diperbarui: ?string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nip' => $this->nip,
            'nama' => $this->name,
            'surel' => $this->email,
            'peran' => $this->role->value,
            'status' => $this->status->value,
            'modul' => $this->assignedModules(),
            'dibuat' => $this->created_at?->toIso8601String(),
            'diperbarui' => $this->updated_at?->toIso8601String(),
        ];
    }
}
