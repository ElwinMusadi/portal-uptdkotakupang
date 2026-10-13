<?php

namespace App\Http\Requests\Dashboard;

/**
 * Changes to a FAQ entry: any of the fields of a new entry, each checked by
 * the same rules when sent.
 */
class UpdateFaqRequest extends StoreFaqRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_map(fn (array $rules): array => ['sometimes', ...$rules], parent::rules());
    }
}
