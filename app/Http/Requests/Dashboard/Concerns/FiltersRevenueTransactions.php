<?php

namespace App\Http\Requests\Dashboard\Concerns;

use App\Actions\BuildRevenueReport;

/**
 * The filters of the revenue report (D-047), shared with Rincian penerimaan
 * (D-079): the values to keep per counter, registration type, vehicle and
 * place, and whether the transaction pays arrears.
 */
trait FiltersRevenueTransactions
{
    /**
     * The rules of the filters.
     *
     * @return array<string, array<int, string>>
     */
    protected function filterRules(): array
    {
        $rules = ['tunggakan' => ['sometimes', 'string', 'in:ada,tidak']];
        foreach (array_keys(BuildRevenueReport::DIMENSIONS) as $filter) {
            $rules[$filter] = ['sometimes', 'list', 'max:500'];
            $rules["{$filter}.*"] = ['string', 'max:80'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function filterMessages(): array
    {
        return [
            'tunggakan.in' => 'Saringan tunggakan tidak dikenal.',
            'list' => 'Saringan :attribute tidak valid.',
            'max' => 'Saringan :attribute terlalu panjang.',
            'string' => 'Saringan :attribute tidak valid.',
        ];
    }

    /**
     * The filters as the messages name them.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $names = ['loket' => 'loket', 'jenisDaftar' => 'jenis daftar', 'jenisKendaraan' => 'jenis kendaraan', 'kepemilikan' => 'kepemilikan',
            'warnaPlat' => 'warna plat', 'merk' => 'merek', 'uptAsal' => 'asal UPT', 'kecamatan' => 'kecamatan', 'kelurahan' => 'kelurahan'];
        $attributes = [];
        foreach ($names as $filter => $name) {
            $attributes[$filter] = $name;
            $attributes["{$filter}.*"] = $name;
        }

        return $attributes;
    }

    /**
     * The chosen values per filter, and the arrears filter when set.
     *
     * @return array<string, list<string>|string>
     */
    public function filters(): array
    {
        return array_filter(
            $this->safe()->only(['tunggakan', ...array_keys(BuildRevenueReport::DIMENSIONS)]),
            fn (mixed $value): bool => $value !== [] && $value !== null,
        );
    }
}
