<?php

namespace App\Http\Requests\Dashboard;

use App\Actions\ReadPotentialRows;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The start of an import of BPAD's potential-tax file (D-052): the file's
 * name, the columns of its header as the dashboard read them, and how many
 * rows it has.
 */
class StartPotentialImportRequest extends FormRequest
{
    /**
     * Rows in one file, at most: a year of Kota Kupang's vehicles fits many
     * times.
     */
    public const int MAX_ROWS = 2_000_000;

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
            'berkas' => ['required', 'string', 'max:200'],
            'kolom' => ['required', 'array', function (string $attribute, mixed $value, Closure $fail): void {
                if ($value !== ReadPotentialRows::HEADER) {
                    $fail('Berkas ini bukan Data Potensi dari sistem BPAD: susunan kolomnya tidak dikenali.');
                }
            }],
            'jumlah' => ['required', 'integer', 'min:1', 'max:'.self::MAX_ROWS],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'berkas.*' => 'Nama berkas tidak valid.',
            'kolom.*' => 'Berkas ini bukan Data Potensi dari sistem BPAD: susunan kolomnya tidak dikenali.',
            'jumlah.required' => 'Berkas tidak berisi baris kendaraan.',
            'jumlah.min' => 'Berkas tidak berisi baris kendaraan.',
            'jumlah.max' => 'Berkas berisi lebih dari 2.000.000 baris.',
            'jumlah.integer' => 'Jumlah baris tidak valid.',
        ];
    }
}
