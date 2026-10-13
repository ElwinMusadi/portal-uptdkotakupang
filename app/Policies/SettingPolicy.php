<?php

namespace App\Policies;

use App\Enums\DashboardModule;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * A settings document belongs to the dashboard module of the same name
 * (D-010), e.g. "jam" to Jam Pelayanan: administrators and the employees
 * assigned to that module may read and save it.
 */
class SettingPolicy
{
    /**
     * Determine whether the user can read the named settings document.
     */
    public function view(User $user, string $name): Response
    {
        return $this->managesSetting($user, $name);
    }

    /**
     * Determine whether the user can save the named settings document.
     */
    public function update(User $user, string $name): Response
    {
        return $this->managesSetting($user, $name);
    }

    private function managesSetting(User $user, string $name): Response
    {
        $module = DashboardModule::from($name);

        return in_array($module->value, $user->managedModules(), true)
            ? Response::allow()
            : Response::deny("Modul {$module->label()} belum ditugaskan kepada Anda.");
    }
}
