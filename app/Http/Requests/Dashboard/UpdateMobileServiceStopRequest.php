<?php

namespace App\Http\Requests\Dashboard;

use App\Models\MobileServiceStop;

/**
 * Changes to a Samsat Keliling stop: any of the fields of a new stop, each
 * checked by the same rules when sent.
 */
class UpdateMobileServiceStopRequest extends StoreMobileServiceStopRequest
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
     * The stop being edited.
     */
    protected function currentStop(): ?MobileServiceStop
    {
        $stop = $this->route('stop');

        return $stop instanceof MobileServiceStop ? $stop : null;
    }
}
