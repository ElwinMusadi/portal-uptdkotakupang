<?php

namespace App\Http\Resources;

use App\Models\PotentialVehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A vehicle of Pemetaan PKB with its owner, for collection (D-052). The list
 * gives what a collector needs at a glance; a single vehicle gives every
 * column of the file.
 *
 * @mixin PotentialVehicle
 */
class PotentialVehicleResource extends JsonResource
{
    /**
     * Whether to give every column.
     */
    private bool $complete = false;

    /**
     * Give every column of the file.
     */
    public function complete(): static
    {
        $this->complete = true;

        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $list = [
            'id' => $this->id,
            'nopol' => $this->nopol,
            'nama' => $this->nama,
            'alamat' => $this->alamat,
            'rt' => $this->rt,
            'rw' => $this->rw,
            'kelurahan' => $this->kelurahan,
            'kecamatan' => $this->kecamatan,
            'telepon' => $this->telepon,
            'jenis' => $this->jenis,
            'roda' => $this->roda?->value,
            'merk' => $this->merk,
            'tahunBuat' => $this->tahun_buat,
            'kepatuhan' => $this->kepatuhan,
            'kelompok' => $this->kelompok->value,
            'tahunTunggak' => $this->tahun_tunggak,
            'sdNotice' => $this->sd_notice?->toDateString(),
            'pokokPajak' => $this->pokok_pajak,
            'dendaBerjalan' => $this->denda_berjalan,
            'pokokTunggak' => $this->pokok_tunggak,
            'dendaTunggak' => $this->denda_tunggak,
            'tagihan' => $this->tagihan,
            'hasilKunjungan' => $this->dtd_status,
        ];

        if (! $this->complete) {
            return $list;
        }

        return [
            ...$list,
            'ktp' => $this->ktp,
            'kecamatanBerkas' => $this->kecamatan_berkas,
            'tipe' => $this->tipe,
            'mesin' => $this->mesin,
            'rangka' => $this->rangka,
            'warnaPlat' => $this->warna_plat,
            'warnaKendaraan' => $this->warna_kendaraan,
            'bbm' => $this->bbm,
            'tanggalPenetapan' => $this->tanggal_penetapan?->toDateString(),
            'uptAsal' => $this->upt_asal,
            'penggunaan' => $this->penggunaan,
            'cyl' => $this->cyl,
            'kodeJr' => $this->kode_jr,
            'sdStnk' => $this->sd_stnk?->toDateString(),
            'rubahBentuk' => $this->rubah_bentuk,
            'njkb' => $this->njkb,
            'njub' => $this->njub,
            'bobot' => $this->bobot,
            'bedaHari' => $this->beda_hari,
            'bedaBulan' => $this->beda_bulan,
            'bulatBulan' => $this->bulat_bulan,
            'tahunTunggakAwal' => $this->tahun_tunggak_awal,
            'peringatan' => $this->peringatan,
            'kegiatanKunjungan' => $this->dtd_kegiatan,
            'petugasKunjungan' => $this->dtd_petugas,
        ];
    }
}
