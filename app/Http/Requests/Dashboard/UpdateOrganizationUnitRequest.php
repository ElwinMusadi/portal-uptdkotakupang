<?php

namespace App\Http\Requests\Dashboard;

use App\Models\OrganizationUnit;

/**
 * Part of a unit. A unit cannot be moved under itself or a unit under it.
 */
class UpdateOrganizationUnitRequest extends StoreOrganizationUnitRequest
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

    protected function currentUnit(): OrganizationUnit
    {
        return $this->route('unit');
    }
}
