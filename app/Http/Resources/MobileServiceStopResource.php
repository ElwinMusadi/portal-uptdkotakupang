<?php

namespace App\Http\Resources;

use App\Models\MobileServiceStop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A schedule entry of service outside the office in the shape of the
 * dashboard's JadwalKeliling model (D-043). The public portal uses
 * PublicMobileServiceStopController instead.
 *
 * @mixin MobileServiceStop
 */
class MobileServiceStopResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{id: string, jenis: string, unit: ?string, nama: string, tanggalMulai: ?string, tanggalSelesai: ?string, hari: list<int>, mulai: string, selesai: string, lokasi: string, kecamatan: string, layanan: string, catatan: string, aktif: bool, urutan: int, dibuat: ?string, diperbarui: ?string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'jenis' => $this->jenis->value,
            'unit' => $this->unit,
            'nama' => $this->nama,
            'tanggalMulai' => $this->tanggal_mulai?->toDateString(),
            'tanggalSelesai' => $this->tanggal_selesai?->toDateString(),
            'hari' => $this->hari,
            'mulai' => $this->mulai,
            'selesai' => $this->selesai,
            'lokasi' => $this->lokasi,
            'kecamatan' => $this->kecamatan,
            'layanan' => $this->layanan,
            'catatan' => $this->catatan,
            'aktif' => $this->aktif,
            'urutan' => $this->urutan,
            'dibuat' => $this->created_at?->toIso8601String(),
            'diperbarui' => $this->updated_at?->toIso8601String(),
        ];
    }
}
