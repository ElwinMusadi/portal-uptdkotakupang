<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\WorkflowStatus;
use App\Http\Requests\Dashboard\Concerns\TakesWorkflowStep;
use App\Models\Documentation;
use Illuminate\Validation\Validator;

/**
 * Part of an activity, a workflow step, or both (TakesWorkflowStep). A
 * published activity always has a photo.
 */
class UpdateDocumentationRequest extends StoreDocumentationRequest
{
    use TakesWorkflowStep;

    /**
     * Get the validation rules that apply to the request: every field is
     * optional, but photos that are sent must be complete.
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
        return [$this->checkWorkflowStep(...), $this->checkPhotos(...), $this->checkPublishedPhotos(...)];
    }

    /**
     * An activity that is published once the request is saved needs a
     * photo for the portal's gallery.
     */
    protected function checkPublishedPhotos(Validator $validator): void
    {
        $published = ($this->nextStatus() ?? $this->currentDocumentation()->status) === WorkflowStatus::Published;
        $photos = $this->has('foto') ? (array) $this->input('foto') : $this->currentDocumentation()->foto;

        if ($validator->errors()->isEmpty() && $published && $photos === []) {
            $validator->errors()->add('foto', 'Unggah minimal satu foto sebelum kegiatan diterbitkan.');
        }
    }

    protected function currentDocumentation(): Documentation
    {
        return $this->route('documentation');
    }

    protected function workflowEntry(): Documentation
    {
        return $this->currentDocumentation();
    }

    protected function workflowNoun(): string
    {
        return 'kegiatan';
    }
}
