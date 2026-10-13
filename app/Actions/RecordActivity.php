<?php

namespace App\Actions;

use App\Enums\DashboardModule;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Support\Str;

class RecordActivity
{
    /**
     * Add an entry to the dashboard activity log, e.g. "menambahkan" a FAQ
     * entry whose question is the target. Long targets are shortened.
     */
    public function handle(User $user, DashboardModule $module, string $action, string $target): Activity
    {
        $activity = new Activity;
        $activity->forceFill([
            'user_id' => $user->getKey(),
            'module' => $module,
            'action' => $action,
            'target' => Str::limit($target, 120),
        ])->save();

        return $activity;
    }
}
