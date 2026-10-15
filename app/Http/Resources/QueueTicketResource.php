<?php

namespace App\Http\Resources;

use App\Models\QueueTicket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A queue number as the dashboard lists and prints it (D-051), with the
 * address its QR holds.
 *
 * @mixin QueueTicket
 */
class QueueTicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{id: string, nomor: string, tanggal: string, layanan: array{kode: string, nama: string}, nopol: ?string, status: string, tahap: ?string, tahapLayanan: list<string>, urlLacak: string, dicetak: ?string, dicetakOleh: ?string, dibatalkan: ?string, alasanBatal: ?string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nomor' => $this->nomor,
            'tanggal' => $this->tanggal,
            'layanan' => ['kode' => $this->service->kode, 'nama' => $this->service->nama],
            'nopol' => $this->plate(),
            'status' => $this->status->value,
            'tahap' => $this->tahap?->value,
            'tahapLayanan' => $this->tahap_layanan,
            'urlLacak' => $this->trackingUrl(),
            'dicetak' => $this->created_at?->toIso8601String(),
            'dicetakOleh' => $this->printedBy?->name,
            'dibatalkan' => $this->dibatalkan_pada?->toIso8601String(),
            'alasanBatal' => $this->alasan_batal,
        ];
    }
}
