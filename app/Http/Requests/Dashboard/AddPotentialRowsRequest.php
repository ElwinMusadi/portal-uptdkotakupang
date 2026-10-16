<?php

namespace App\Http\Requests\Dashboard;

use App\Actions\ImportPotentialVehicles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * A part of the potential-tax file's rows (D-052): where it starts in the
 * file (0 for the first row) and up to a thousand rows, each a list of the
 * file's texts.
 */
class AddPotentialRowsRequest extends FormRequest
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
     * Get the validation rules that apply to the request. The cells are
     * checked after, without a rule per cell.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'mulai' => ['required', 'integer', 'min:0'],
            'baris' => ['required', 'array', 'list', 'max:'.ImportPotentialVehicles::ROWS_PER_PART],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mulai.*' => 'Letak bagian berkas tidak valid.',
            'baris.required' => 'Bagian berkas tidak berisi baris.',
            'baris.max' => 'Satu bagian berisi paling banyak '.ImportPotentialVehicles::ROWS_PER_PART.' baris.',
            'baris.*' => 'Bagian berkas tidak bisa dibaca.',
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }
                foreach ($this->input('baris') as $index => $row) {
                    if (! is_array($row) || ! array_is_list($row) || array_filter($row, fn (mixed $cell): bool => ! is_string($cell) && $cell !== null) !== []) {
                        $validator->errors()->add('baris', 'Baris '.((int) $this->input('mulai') + $index + 1).' tidak bisa dibaca.');

                        return;
                    }
                }
            },
        ];
    }

    /**
     * The rows, each a list of texts.
     *
     * @return list<list<string>>
     */
    public function rows(): array
    {
        return array_map(fn (array $row): array => array_map(fn (?string $cell): string => (string) $cell, $row), $this->validated('baris'));
    }
}
