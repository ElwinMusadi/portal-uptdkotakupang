<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\Kecamatan;
use App\Enums\TaxCompliance;
use App\Enums\VehicleWheels;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A search of the vehicles of Pemetaan PKB for collection (D-052): part of
 * the plate number or the owner's name, the kecamatan and kelurahan, the
 * compliance group, the wheel class, the years in arrears and the due month,
 * each optional.
 */
class ListPotentialVehiclesRequest extends FormRequest
{
    /**
     * The kecamatan filter for vehicles whose kelurahan is empty or outside
     * Kota Kupang's list.
     */
    public const string UNPLACED = 'tanpa';

    /**
     * Determine if the user is authorized to make this request; the route
     * checks PotentialImportPolicy.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'nullable', 'string', 'max:100'],
            'kecamatan' => ['sometimes', Rule::in([...array_column(Kecamatan::cases(), 'value'), self::UNPLACED])],
            'kelurahan' => ['sometimes', 'string', 'max:80'],
            'kelompok' => ['sometimes', Rule::enum(TaxCompliance::class)],
            'roda' => ['sometimes', Rule::enum(VehicleWheels::class)],
            'tahun' => ['sometimes', 'integer', 'between:0,99'],
            'bulan' => ['sometimes', 'integer', 'between:1,12'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'q.*' => 'Teks pencarian terlalu panjang.',
            'kecamatan.*' => 'Kecamatan ini tidak ada di Kota Kupang.',
            'kelurahan.*' => 'Kelurahan tidak valid.',
            'kelompok.*' => 'Status kepatuhan ini tidak ada.',
            'roda.*' => 'Golongan roda ini tidak ada.',
            'tahun.*' => 'Lama tunggakan tidak valid.',
            'bulan.*' => 'Bulan jatuh tempo harus 1 sampai 12.',
        ];
    }

    /**
     * The kecamatan as stored: empty for vehicles not placed, null without
     * this filter.
     */
    public function kecamatan(): ?string
    {
        $kecamatan = $this->validated('kecamatan');

        return $kecamatan === self::UNPLACED ? '' : $kecamatan;
    }
}
