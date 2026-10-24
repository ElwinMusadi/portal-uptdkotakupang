<?php

namespace App\Http\Resources;

use App\Actions\ReadStsFile;
use App\Models\RevenueTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An STS transaction as Rincian penerimaan shows it (D-079): the columns the
 * report keeps and its details, every detail null when they are not kept.
 * Only the dashboard has it; the portal never shows a transaction.
 *
 * @mixin RevenueTransaction
 */
class RevenueDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, int|string|null>
     */
    public function toArray(Request $request): array
    {
        $details = $this->rincian ?? [];

        return [
            'id' => (string) $this->id,
            'loket' => $this->loket,
            'namaLoket' => (string) $this->getAttribute('nama_loket'),
            'tanggal' => $this->tanggal->toDateString(),
            'jenisDaftar' => $this->jenis_daftar,
            'nomoratur' => $this->nomoratur,
            'jnsKend' => $this->jenis_kendaraan,
            'merk' => $this->merk,
            'warnaPlat' => $this->warna_plat,
            'uptAsal' => $this->upt_asal,
            'kelurahan' => $this->kelurahan,
            'kecamatan' => $this->kecamatan,
            'tahunTunggakan' => $this->tahun_tunggakan,
            'pkb' => $this->pkb,
            'bbn' => $this->bbn,
            'dendaPkb' => $this->denda_pkb,
            'dendaBbn' => $this->denda_bbn,
            'tunggakan' => $this->tunggakan,
            'dendaTunggakan' => $this->denda_tunggakan,
            'opsenPkb' => $this->opsen_pkb,
            'opsenBbn' => $this->opsen_bbn,
            'opsenDendaPkb' => $this->opsen_denda_pkb,
            'opsenDendaBbn' => $this->opsen_denda_bbn,
            'opsenTunggakan' => $this->opsen_tunggakan,
            'opsenDendaTunggakan' => $this->opsen_denda_tunggakan,
            'jumlah' => $this->jumlah,
            ...array_map(fn (string $name): ?string => $details[$name] ?? null, array_combine(array_keys(ReadStsFile::DETAIL_COLUMNS), array_keys(ReadStsFile::DETAIL_COLUMNS))),
        ];
    }
}
