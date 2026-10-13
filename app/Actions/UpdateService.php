<?php

namespace App\Actions;

use App\Enums\DashboardModule;
use App\Enums\WorkflowStatus;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateService
{
    public function __construct(private RecordActivity $recordActivity) {}

    /**
     * Change a service's content and take a workflow step, in one
     * transaction. A step is recorded in the service's history under the
     * employee's name and logged by its verb; returning keeps the note for
     * the provider, submitting for review clears it, and publishing stamps
     * the time. A content change alone is logged as "memperbarui"; an
     * unchanged service is not logged.
     *
     * @param  array<string, mixed>  $content
     */
    public function handle(User $user, Service $service, array $content, ?WorkflowStatus $next, string $note): Service
    {
        DB::transaction(function () use ($user, $service, $content, $next, $note): void {
            $service->fill($content);

            if ($next !== null) {
                $verb = $service->status->steps()[$next->value];
                $service->moveTo($next, $user->name, $verb, $next === WorkflowStatus::Returned ? $note : '');
                $service->catatan_revisi = match ($next) {
                    WorkflowStatus::Returned => $note,
                    WorkflowStatus::UnderReview => '',
                    default => $service->catatan_revisi,
                };

                if ($next === WorkflowStatus::Published) {
                    $service->terbit_pada = now();
                }

                $service->save();
                $this->recordActivity->handle($user, DashboardModule::Layanan, $verb, $service->nama);

                return;
            }

            if ($service->isDirty()) {
                $service->save();
                $this->recordActivity->handle($user, DashboardModule::Layanan, 'memperbarui', $service->nama);
            }
        });

        return $service;
    }
}
