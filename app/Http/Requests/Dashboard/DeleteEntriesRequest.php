<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The entries of a dashboard collection to delete. Entries that no longer
 * exist are skipped, so a repeated request changes nothing.
 */
abstract class DeleteEntriesRequest extends FormRequest
{
    /**
     * The entries' name in messages, e.g. "tanya jawab".
     */
    abstract protected function entryName(): string;

    /**
     * Determine if the user is authorized to make this request. The route
     * authorizes it through the collection's policy.
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
            'ids' => ['required', 'list', 'max:500'],
            'ids.*' => ['string', 'ulid', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $name = $this->entryName();

        return [
            'ids.required' => "Pilih {$name} yang akan dihapus.",
            'ids.list' => "Daftar {$name} tidak valid.",
            'ids.max' => "Terlalu banyak {$name} sekaligus.",
            'ids.*.string' => "Daftar {$name} tidak valid.",
            'ids.*.ulid' => "Daftar {$name} tidak valid.",
            'ids.*.distinct' => "Daftar {$name} berisi entri ganda.",
        ];
    }

    /**
     * @return list<string>
     */
    public function ids(): array
    {
        return $this->validated('ids');
    }
}
