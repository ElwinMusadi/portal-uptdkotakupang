<?php

namespace App\Http\Requests\Dashboard;

use App\Models\ServiceLocation;

/**
 * Changes to a place in the list: any of its fields, each checked by the
 * same rules when sent. Schedule entries keep the name they were saved with.
 */
class UpdateServiceLocationRequest extends StoreServiceLocationRequest
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

    /**
     * The place being edited.
     */
    protected function currentLocation(): ?ServiceLocation
    {
        $location = $this->route('location');

        return $location instanceof ServiceLocation ? $location : null;
    }
}
