<?php

namespace App\Http\Requests\Dashboard;

use App\Http\Requests\Dashboard\Concerns\TakesWorkflowStep;
use App\Models\Article;
use Illuminate\Validation\Validator;

/**
 * Part of an article, a workflow step, or both (TakesWorkflowStep).
 */
class UpdateArticleRequest extends StoreArticleRequest
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
        return [$this->checkWorkflowStep(...), $this->checkCover(...)];
    }

    protected function currentArticle(): Article
    {
        return $this->route('article');
    }

    protected function workflowEntry(): Article
    {
        return $this->currentArticle();
    }

    protected function workflowNoun(): string
    {
        return 'tulisan';
    }
}
