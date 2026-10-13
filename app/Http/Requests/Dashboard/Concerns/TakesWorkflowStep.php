<?php

namespace App\Http\Requests\Dashboard\Concerns;

use App\Enums\WorkflowStatus;
use App\Rules\PlainText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A change request that may also take a workflow step (D-022). "status"
 * takes the entry to that status when the workflow allows it from the
 * current one; returning needs "catatanRevisi". "riwayat" and "terbitPada"
 * are written only by the server.
 */
trait TakesWorkflowStep
{
    /**
     * The entry the request changes, from the route.
     */
    abstract protected function workflowEntry(): Model;

    /**
     * What the entry is called in messages, e.g. "layanan".
     */
    abstract protected function workflowNoun(): string;

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function workflowRules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(WorkflowStatus::class)],
            'catatanRevisi' => ['nullable', 'string', 'max:400', new PlainText('Catatan revisi')],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function workflowMessages(): array
    {
        return [
            'status.enum' => 'Status alur tidak valid.',
            'catatanRevisi.string' => 'Catatan revisi tidak valid.',
            'catatanRevisi.max' => 'Catatan revisi maksimal :max karakter.',
        ];
    }

    /**
     * A step must be one the workflow allows from the entry's status, and
     * returning an entry needs a note for its provider.
     */
    protected function checkWorkflowStep(Validator $validator): void
    {
        $next = $this->nextStatus();
        $current = $this->workflowEntry()->status;

        if ($validator->errors()->has('status') || $next === null) {
            return;
        }

        if (! $current->canMoveTo($next)) {
            $validator->errors()->add('status', "Langkah ini tidak tersedia untuk {$this->workflowNoun()} berstatus {$current->label()}.");
        } elseif ($next === WorkflowStatus::Returned && $this->revisionNote() === '') {
            $validator->errors()->add('catatanRevisi', 'Tulis catatan revisi agar penyedia tahu apa yang perlu diperbaiki.');
        }
    }

    /**
     * The status to take the entry to, or null when it stays.
     */
    public function nextStatus(): ?WorkflowStatus
    {
        $status = WorkflowStatus::tryFrom((string) $this->input('status'));

        return $status === $this->workflowEntry()->status ? null : $status;
    }

    /**
     * The note for the provider when the entry is returned.
     */
    public function revisionNote(): string
    {
        return trim((string) $this->input('catatanRevisi'));
    }
}
