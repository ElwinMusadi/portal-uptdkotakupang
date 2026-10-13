<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\WorkflowStatus;
use App\Http\Requests\Dashboard\Concerns\TakesWorkflowStep;
use App\Models\Document;
use Illuminate\Validation\Validator;

/**
 * Part of a document, a workflow step, or both (TakesWorkflowStep).
 */
class UpdateDocumentRequest extends StoreDocumentRequest
{
    use TakesWorkflowStep;

    /**
     * Get the validation rules that apply to the request: every field is
     * optional, but a file that is sent must have its address.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [];

        foreach (parent::rules() as $field => $fieldRules) {
            $rules[$field] = str_contains($field, '.') ? $fieldRules : ['sometimes', ...$fieldRules];
        }

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
        return [$this->checkWorkflowStep(...), $this->checkDocument(...)];
    }

    protected function currentDocument(): Document
    {
        return $this->route('document');
    }

    protected function resultingStatusIsPublished(): bool
    {
        return ($this->nextStatus() ?? $this->currentDocument()->status) === WorkflowStatus::Published;
    }

    protected function workflowEntry(): Document
    {
        return $this->currentDocument();
    }

    protected function workflowNoun(): string
    {
        return 'dokumen';
    }
}
