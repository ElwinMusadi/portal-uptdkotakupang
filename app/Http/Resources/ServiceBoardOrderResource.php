<?php

namespace App\Http\Resources;

use App\Models\ServiceBoardOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ServiceBoardOrder
 */
class ServiceBoardOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{tanggal: string, urutan: list<string>}
     */
    public function toArray(Request $request): array
    {
        return [
            'tanggal' => $this->tanggal,
            'urutan' => $this->urutan,
        ];
    }
}
