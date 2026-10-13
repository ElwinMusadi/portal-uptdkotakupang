<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The FAQ entries to delete. Entries that no longer exist are skipped, so a
 * repeated request changes nothing.
 */
class DeleteFaqsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request. The route
     * authorizes it through FaqPolicy.
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
        return [
            'ids.required' => 'Pilih tanya jawab yang akan dihapus.',
            'ids.list' => 'Daftar tanya jawab tidak valid.',
            'ids.max' => 'Terlalu banyak tanya jawab sekaligus.',
            'ids.*.string' => 'Daftar tanya jawab tidak valid.',
            'ids.*.ulid' => 'Daftar tanya jawab tidak valid.',
            'ids.*.distinct' => 'Daftar tanya jawab berisi entri ganda.',
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
