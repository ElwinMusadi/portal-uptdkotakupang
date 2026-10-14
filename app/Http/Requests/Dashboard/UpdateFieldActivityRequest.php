<?php

namespace App\Http\Requests\Dashboard;

/**
 * Part of a field activity: its details, its officers, or both.
 */
class UpdateFieldActivityRequest extends StoreFieldActivityRequest
{
    /**
     * Get the validation rules that apply to the request: every field is
     * optional.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_map(fn (array $rules): array => ['sometimes', ...$rules], parent::rules());
    }
}
