<?php

namespace App\Http\Requests\Dashboard;

use App\Http\Requests\Dashboard\Concerns\TakesWorkflowStep;
use App\Models\Service;
use Illuminate\Validation\Validator;

/**
 * Part of a service, a workflow step, or both (TakesWorkflowStep).
 */
class UpdateServiceRequest extends StoreServiceRequest
{
    use TakesWorkflowStep;

    /**
     * Get the validation rules that apply to the request: every content
     * field is optional.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = array_map(fn (array $rules): array => ['sometimes', ...$rules], parent::rules());

        return [...$rules, ...$this->workflowRules()];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [...parent::messages(), ...$this->workflowMessages()];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [$this->checkWorkflowStep(...)];
    }

    protected function workflowEntry(): Service
    {
        return $this->route('service');
    }

    protected function workflowNoun(): string
    {
        return 'layanan';
    }
}
