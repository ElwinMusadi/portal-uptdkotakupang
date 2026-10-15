<?php

namespace App\Http\Resources;

use App\Models\RevenueImport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An STS import as the dashboard's import history shows it (D-047).
 *
 * @mixin RevenueImport
 */
class RevenueImportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{id: string, loket: string, namaLoket: string, namaBerkas: string, tanggalAwal: string, tanggalAkhir: string, jumlahTransaksi: int, jumlahDilewati: int, total: int, tampil: bool, oleh: ?string, dibuat: ?string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'loket' => $this->loket,
            'namaLoket' => $this->nama_loket,
            'namaBerkas' => $this->nama_berkas,
            'tanggalAwal' => $this->tanggal_awal->toDateString(),
            'tanggalAkhir' => $this->tanggal_akhir->toDateString(),
            'jumlahTransaksi' => $this->jumlah_transaksi,
            'jumlahDilewati' => $this->jumlah_dilewati,
            'total' => $this->total,
            'tampil' => $this->tampil,
            'oleh' => $this->user?->name,
            'dibuat' => $this->created_at?->toIso8601String(),
        ];
    }
}
