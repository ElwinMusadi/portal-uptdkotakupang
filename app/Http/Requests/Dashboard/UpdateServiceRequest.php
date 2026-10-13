<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\WorkflowStatus;
use App\Models\Service;
use App\Rules\PlainText;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Part of a service, a workflow step, or both. "status" takes the step to
 * that status when the workflow allows it from the current one; returning
 * needs "catatanRevisi". "riwayat" and "terbitPada" are written only by the
 * server (D-022).
 */
class UpdateServiceRequest extends StoreServiceRequest
{
    /**
     * Get the validation rules that apply to the request: every content
     * field is optional.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = array_map(fn (array $rules): array => ['sometimes', ...$rules], parent::rules());

        return [
            ...$rules,
            'status' => ['sometimes', Rule::enum(WorkflowStatus::class)],
            'catatanRevisi' => ['nullable', 'string', 'max:400', new PlainText('Catatan revisi')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'status.enum' => 'Status alur tidak valid.',
            'catatanRevisi.string' => 'Catatan revisi tidak valid.',
            'catatanRevisi.max' => 'Catatan revisi maksimal :max karakter.',
        ];
    }

    /**
     * A step must be one the workflow allows from the service's status, and
     * returning a service needs a note for its provider.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $next = $this->nextStatus();
                $current = $this->service()->status;

                if ($validator->errors()->has('status') || $next === null) {
                    return;
                }

                if (! $current->canMoveTo($next)) {
                    $validator->errors()->add('status', "Langkah ini tidak tersedia untuk layanan berstatus {$current->label()}.");
                } elseif ($next === WorkflowStatus::Returned && $this->revisionNote() === '') {
                    $validator->errors()->add('catatanRevisi', 'Tulis catatan revisi agar penyedia tahu apa yang perlu diperbaiki.');
                }
            },
        ];
    }

    /**
     * The status to take the service to, or null when it stays.
     */
    public function nextStatus(): ?WorkflowStatus
    {
        $status = WorkflowStatus::tryFrom((string) $this->input('status'));

        return $status === $this->service()->status ? null : $status;
    }

    /**
     * The note for the provider when the service is returned.
     */
    public function revisionNote(): string
    {
        return trim((string) $this->input('catatanRevisi'));
    }

    private function service(): Service
    {
        return $this->route('service');
    }
}
