<?php

namespace App\Models\Concerns;

use App\Enums\WorkflowStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Content that goes through the publication workflow (D-022): a status, the
 * time it was published, and "riwayat", the workflow steps newest first,
 * written only by the server. Only published entries reach the portal.
 *
 * @property WorkflowStatus $status
 * @property ?Carbon $terbit_pada
 * @property list<array{waktu: string, oleh: string, aksi: string, ke: string, catatan?: string}> $riwayat
 */
trait HasPublicationWorkflow
{
    /**
     * Cast the workflow columns.
     */
    public function initializeHasPublicationWorkflow(): void
    {
        $this->mergeCasts([
            'status' => WorkflowStatus::class,
            'terbit_pada' => 'datetime',
            'riwayat' => 'array',
        ]);
    }

    /**
     * Limit the query to entries on the portal.
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', WorkflowStatus::Published->value);
    }

    /**
     * Put the entry in a workflow status and record the step first in its
     * history, as the dashboard shows it: who, what, the new status and the
     * note when there is one.
     */
    public function moveTo(WorkflowStatus $status, string $by, string $verb, string $note = ''): void
    {
        $this->status = $status;
        $this->riwayat = [
            ['waktu' => now()->toIso8601String(), 'oleh' => $by, 'aksi' => $verb, 'ke' => $status->value, ...($note !== '' ? ['catatan' => $note] : [])],
            ...($this->riwayat ?? []),
        ];
    }
}
