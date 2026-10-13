<?php

namespace App\Http\Requests\Dashboard;

/**
 * Changes to an update standard: any of the fields of a new standard, each
 * checked by the same rules when sent.
 */
class UpdateUpdateStandardRequest extends StoreUpdateStandardRequest
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
