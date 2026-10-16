<?php

namespace App\Http\Requests\Dashboard;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The columns of the Pemetaan PKB tables (D-052): those of the kecamatan and
 * kelurahan tables, which the portal follows too, and those of the
 * collection list; each table keeps at least one.
 */
class UpdatePotentialMapColumnsRequest extends FormRequest
{
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
            'wilayah' => ['required', 'array', 'min:1'],
            'wilayah.*' => ['required', 'string', 'distinct', Rule::in(Setting::POTENTIAL_MAP_REGION_COLUMNS)],
            'sasaran' => ['required', 'array', 'min:1'],
            'sasaran.*' => ['required', 'string', 'distinct', Rule::in(Setting::POTENTIAL_MAP_TARGET_COLUMNS)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'wilayah.required' => 'Pilih sedikitnya satu kolom untuk tabel kecamatan dan kelurahan.',
            'wilayah.min' => 'Pilih sedikitnya satu kolom untuk tabel kecamatan dan kelurahan.',
            'wilayah.*.distinct' => 'Kolom tabel kecamatan dan kelurahan dipilih lebih dari sekali.',
            'wilayah.*' => 'Kolom tabel kecamatan dan kelurahan tidak dikenal.',
            'sasaran.required' => 'Pilih sedikitnya satu kolom untuk daftar sasaran penagihan.',
            'sasaran.min' => 'Pilih sedikitnya satu kolom untuk daftar sasaran penagihan.',
            'sasaran.*.distinct' => 'Kolom daftar sasaran penagihan dipilih lebih dari sekali.',
            'sasaran.*' => 'Kolom daftar sasaran penagihan tidak dikenal.',
        ];
    }

    /**
     * The chosen columns of both tables, in the tables' own order.
     *
     * @return array{sasaran: list<string>, wilayah: list<string>}
     */
    public function columns(): array
    {
        return [
            'sasaran' => array_values(array_intersect(Setting::POTENTIAL_MAP_TARGET_COLUMNS, $this->validated('sasaran'))),
            'wilayah' => array_values(array_intersect(Setting::POTENTIAL_MAP_REGION_COLUMNS, $this->validated('wilayah'))),
        ];
    }
}
