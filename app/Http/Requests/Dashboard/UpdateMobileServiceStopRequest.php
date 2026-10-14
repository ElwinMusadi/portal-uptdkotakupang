<?php

namespace App\Http\Requests\Dashboard;

use App\Models\MobileServiceStop;

/**
 * Changes to a schedule entry of service outside the office: any of the
 * fields of a new entry, each checked by the same rules when sent, and the
 * changed entry checked as a whole for its kind.
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
