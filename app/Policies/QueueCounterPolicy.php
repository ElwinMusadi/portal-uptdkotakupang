<?php

namespace App\Policies;

use App\Enums\DashboardModule;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * The counters of Antrean Online (D-051): everyone in the module reads them
 * to call numbers; only administrators add, change or remove them.
 */
class QueueCounterPolicy
{
    /**
     * Determine whether the user can list the counters.
     */
    public function viewAny(User $user): Response
    {
        return in_array(DashboardModule::Antrean->value, $user->managedModules(), true)
            ? Response::allow()
            : Response::deny('Modul Antrean Online belum ditugaskan kepada Anda.');
    }

    /**
     * Determine whether the user can add, change or remove counters.
     */
    public function manage(User $user): Response
    {
        return $user->isAdministrator()
            ? Response::allow()
            : Response::deny('Hanya Administrator yang dapat mengatur loket antrean.');
    }
}
