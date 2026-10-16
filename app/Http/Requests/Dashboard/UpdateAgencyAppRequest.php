<?php

namespace App\Http\Requests\Dashboard;

use App\Models\AgencyApp;
use Illuminate\Support\Arr;

/**
 * Part of an app. Its logo may be sent back unchanged, including a logo of
 * the first list, which is no upload; a new logo is an upload.
 */
class UpdateAgencyAppRequest extends StoreAgencyAppRequest
{
    /**
     * Prepare the data for validation: an unchanged logo, by its stored
     * value or its full address, is not a change.
     */
    protected function prepareForValidation(): void
    {
        $app = $this->currentApp();

        if (in_array($this->input('logo'), [$app->logo, $app->logoUrl()], true)) {
            $this->replace(Arr::except($this->all(), 'logo'));
        }
    }

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

    protected function currentApp(): AgencyApp
    {
        return $this->route('agencyApp');
    }
}
