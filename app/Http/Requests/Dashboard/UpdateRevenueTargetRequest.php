<?php

namespace App\Http\Requests\Dashboard;

use App\Models\RevenueTarget;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * A year's revenue target and, when the STS imports start after January, the
 * realisation of the months before them (D-068): combined, and of the
 * vehicles registered at the city's UPT for Form I.I ("kotaKupang", D-101)
 * over the same months. Amounts are whole rupiah.
 */
class UpdateRevenueTargetRequest extends FormRequest
{
    /**
     * Largest amount accepted, still exact as a JavaScript number.
     */
    public const int MAX_AMOUNT = 999_999_999_999_999;

    /**
     * The targets as the request names them, with their column.
     *
     * @var array<string, string>
     */
    private const array TARGETS = ['pkb' => 'target_pkb', 'bbn' => 'target_bbn', 'dendaPkb' => 'target_denda_pkb', 'dendaBbn' => 'target_denda_bbn'];

    /**
     * Determine if the user is authorized to make this request. The route
     * authorizes it through RevenueImportPolicy.
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
        $amount = ['integer', 'min:0', 'max:'.self::MAX_AMOUNT];
        $opening = is_array($this->input('saldoAwal'));
        // Each city amount is asked for once the city's balance is sent at all, so a missing one is a single message
        $city = $opening && is_array($this->input('saldoAwal.kotaKupang'));

        $rules = [
            'target' => ['required', 'array:'.implode(',', array_keys(self::TARGETS))],
            'saldoAwal' => ['present', 'nullable', 'array:sampaiBulan,kotaKupang,'.implode(',', $this->balanceNames())],
            'saldoAwal.sampaiBulan' => [Rule::requiredIf($opening), 'integer', 'between:1,12'],
            'saldoAwal.kotaKupang' => [Rule::requiredIf($opening), 'array:'.implode(',', $this->balanceNames())],
        ];

        foreach (array_keys(self::TARGETS) as $name) {
            $rules["target.{$name}"] = ['required', ...$amount];
        }

        foreach ($this->balanceNames() as $name) {
            $rules["saldoAwal.{$name}"] = [Rule::requiredIf($opening), ...$amount];
            $rules["saldoAwal.kotaKupang.{$name}"] = [Rule::requiredIf($city), ...$amount];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'target.required' => 'Isi target penerimaan.',
            'target.array' => 'Target penerimaan tidak valid.',
            'target.*.required' => 'Isi target :attribute.',
            'target.*.integer' => 'Target :attribute harus berupa angka rupiah tanpa desimal.',
            'target.*.min' => 'Target :attribute tidak boleh negatif.',
            'target.*.max' => 'Target :attribute terlalu besar.',
            'saldoAwal.present' => 'Pilih apakah ada saldo awal.',
            'saldoAwal.array' => 'Saldo awal tidak valid.',
            'saldoAwal.sampaiBulan.required' => 'Pilih bulan terakhir yang tercakup saldo awal.',
            'saldoAwal.sampaiBulan.*' => 'Bulan saldo awal tidak valid.',
            'saldoAwal.kotaKupang.required' => 'Isi saldo awal Kota Kupang.',
            'saldoAwal.kotaKupang.array' => 'Saldo awal Kota Kupang tidak valid.',
            'saldoAwal.kotaKupang.*.required' => 'Isi saldo awal :attribute.',
            'saldoAwal.kotaKupang.*.integer' => 'Saldo awal :attribute harus berupa angka rupiah tanpa desimal.',
            'saldoAwal.kotaKupang.*.min' => 'Saldo awal :attribute tidak boleh negatif.',
            'saldoAwal.kotaKupang.*.max' => 'Saldo awal :attribute terlalu besar.',
            'saldoAwal.*.required' => 'Isi saldo awal :attribute.',
            'saldoAwal.*.integer' => 'Saldo awal :attribute harus berupa angka rupiah tanpa desimal.',
            'saldoAwal.*.min' => 'Saldo awal :attribute tidak boleh negatif.',
            'saldoAwal.*.max' => 'Saldo awal :attribute terlalu besar.',
        ];
    }

    /**
     * The kinds of revenue as the messages name them.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = ['target.pkb' => 'PKB', 'target.bbn' => 'BBNKB', 'target.dendaPkb' => 'denda PKB', 'target.dendaBbn' => 'denda BBNKB'];
        $labels = ['pkb' => 'PKB', 'tunggakan' => 'PKB tunggakan', 'bbn' => 'BBNKB', 'denda_pkb' => 'denda PKB',
            'denda_tunggakan' => 'denda PKB tunggakan', 'denda_bbn' => 'denda BBNKB'];

        foreach ($labels as $part => $label) {
            $attributes['saldoAwal.'.Str::camel($part)] = $label;
            $attributes['saldoAwal.kotaKupang.'.Str::camel($part)] = "{$label} Kota Kupang";
        }

        return $attributes;
    }

    /**
     * The columns to save: the targets, and the opening balances or none.
     *
     * @return array<string, ?int>
     */
    public function columns(): array
    {
        $columns = [];
        foreach (self::TARGETS as $name => $column) {
            $columns[$column] = (int) $this->input("target.{$name}");
        }

        $opening = $this->input('saldoAwal');
        $columns['saldo_sampai_bulan'] = is_array($opening) ? (int) $opening['sampaiBulan'] : null;
        foreach (RevenueTarget::PARTS as $part) {
            $columns["saldo_{$part}"] = is_array($opening) ? (int) $opening[Str::camel($part)] : 0;
            $columns["saldo_kota_{$part}"] = is_array($opening) ? (int) $opening['kotaKupang'][Str::camel($part)] : null;
        }

        return $columns;
    }

    /**
     * The opening balance's amounts as the request names them.
     *
     * @return list<string>
     */
    private function balanceNames(): array
    {
        return array_map(fn (string $part): string => Str::camel($part), RevenueTarget::PARTS);
    }
}
