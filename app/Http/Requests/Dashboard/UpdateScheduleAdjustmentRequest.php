<?php

namespace App\Http\Requests\Dashboard;

use App\Models\ScheduleAdjustment;

/**
 * Changes to a schedule adjustment: any of the fields of a new one, each
 * checked by the same rules when sent.
 */
class UpdateScheduleAdjustmentRequest extends StoreScheduleAdjustmentRequest
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
     * The adjustment being edited.
     */
    protected function currentAdjustment(): ?ScheduleAdjustment
    {
        $adjustment = $this->route('adjustment');

        return $adjustment instanceof ScheduleAdjustment ? $adjustment : null;
    }
}
