<?php

namespace App\Actions;

use App\Enums\DashboardModule;
use App\Enums\WorkflowStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UpdateWorkflowEntry
{
    public function __construct(private RecordActivity $recordActivity) {}

    /**
     * Change an entry's content and take a workflow step, in one
     * transaction (D-022). A step is recorded in the entry's history under
     * the employee's name and logged by its verb; returning keeps the note
     * for the provider, submitting for review clears it, and publishing
     * stamps the time. A content change alone is logged as "memperbarui";
     * an unchanged entry is not logged. $afterSave runs in the same
     * transaction, e.g. to keep or remove the entry's files.
     *
     * @template TEntry of Model
     *
     * @param  TEntry  $entry  A model using HasPublicationWorkflow.
     * @param  string  $titleAttribute  The attribute naming the entry in the log, e.g. "nama".
     * @param  array<string, mixed>  $content
     * @param  ?callable(TEntry): void  $afterSave
     * @return TEntry
     */
    public function handle(User $user, Model $entry, DashboardModule $module, string $titleAttribute, array $content, ?WorkflowStatus $next, string $note, ?callable $afterSave = null): Model
    {
        DB::transaction(function () use ($user, $entry, $module, $titleAttribute, $content, $next, $note, $afterSave): void {
            $entry->fill($content);

            if ($next !== null) {
                $verb = $entry->status->steps()[$next->value];
                $entry->moveTo($next, $user->name, $verb, $next === WorkflowStatus::Returned ? $note : '');
                $entry->catatan_revisi = match ($next) {
                    WorkflowStatus::Returned => $note,
                    WorkflowStatus::UnderReview => '',
                    default => $entry->catatan_revisi,
                };

                if ($next === WorkflowStatus::Published) {
                    $entry->terbit_pada = now();
                }

                $entry->save();
                $this->recordActivity->handle($user, $module, $verb, (string) $entry->getAttribute($titleAttribute));
            } elseif ($entry->isDirty()) {
                $entry->save();
                $this->recordActivity->handle($user, $module, 'memperbarui', (string) $entry->getAttribute($titleAttribute));
            }

            if ($afterSave !== null) {
                $afterSave($entry);
            }
        });

        return $entry;
    }
}
