<?php

namespace App\Http\Requests\Dashboard;

use App\Models\OrganizationUnit;
use Illuminate\Validation\Validator;

/**
 * The units to delete. A unit that other units report to can only be
 * deleted together with them.
 */
class DeleteOrganizationUnitsRequest extends DeleteEntriesRequest
{
    protected function entryName(): string
    {
        return 'unit';
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $ids = (array) $this->input('ids');

                if (! $validator->errors()->has('ids') && OrganizationUnit::query()->whereIn('induk', $ids)->whereKeyNot($ids)->exists()) {
                    $validator->errors()->add('ids', 'Unit masih membawahi unit lain. Pindahkan unit di bawahnya terlebih dahulu.');
                }
            },
        ];
    }
}
