<?php

namespace App\Policies;

use App\Enums\DashboardModule;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * The services of Antrean Online (D-051): everyone in the module reads them to
 * print numbers; only administrators change their names and stages.
 */
class QueueServicePolicy
{
    /**
     * Determine whether the user can list the services.
     */
    public function viewAny(User $user): Response
    {
        return in_array(DashboardModule::Antrean->value, $user->managedModules(), true)
            ? Response::allow()
            : Response::deny('Modul Antrean Online belum ditugaskan kepada Anda.');
    }

    /**
     * Determine whether the user can change a service.
     */
    public function update(User $user): Response
    {
        return $user->isAdministrator()
            ? Response::allow()
            : Response::deny('Hanya Administrator yang dapat mengubah layanan antrean.');
    }
}
