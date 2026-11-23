<?php

namespace App\Http\Resources;

use App\Models\RevenueTarget;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * A year's revenue target and opening balance as the realisation page edits
 * them (D-068), the city's opening balance of Form I.I under "kotaKupang"
 * (D-101): null when the year was saved before it.
 *
 * @mixin RevenueTarget
 */
class RevenueTargetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{tahun: int, pkb: int, bbn: int, dendaPkb: int, dendaBbn: int, saldoAwal: ?array<string, mixed>, diperbarui: ?string, oleh: ?string}
     */
    public function toArray(Request $request): array
    {
        $balance = null;
        if ($this->saldo_sampai_bulan !== null) {
            $balance = ['sampaiBulan' => $this->saldo_sampai_bulan];
            $city = [];
            foreach (RevenueTarget::PARTS as $part) {
                $balance[Str::camel($part)] = $this->{"saldo_{$part}"};
                $city[Str::camel($part)] = $this->{"saldo_kota_{$part}"};
            }
            $balance['kotaKupang'] = in_array(null, $city, true) ? null : $city;
        }

        return [
            'tahun' => $this->tahun,
            'pkb' => $this->target_pkb,
            'bbn' => $this->target_bbn,
            'dendaPkb' => $this->target_denda_pkb,
            'dendaBbn' => $this->target_denda_bbn,
            'saldoAwal' => $balance,
            'diperbarui' => $this->updated_at?->toIso8601String(),
            'oleh' => $this->user?->name,
        ];
    }
}
