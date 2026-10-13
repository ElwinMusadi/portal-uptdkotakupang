<?php

namespace App\Policies;

use App\Enums\DashboardModule;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * A settings document belongs to the dashboard module of the same name
 * (D-010), e.g. "jam" to Jam Pelayanan: administrators and the employees
 * assigned to that module may read and save it. The tariff is the exception
 * for reading (D-017), and the site settings, which belong to no module, for
 * both (D-026).
 */
class SettingPolicy
{
    /**
     * Determine whether the user can read the named settings document. Every
     * employee may read the tariff: the Kalkulator PKB is open to all of them
     * (D-010), and the portal publishes the same rates.
     */
    public function view(User $user, string $name): Response
    {
        if ($name === Setting::TARIFF || $name === Setting::SITE) {
            return Response::allow();
        }

        return $this->managesSetting($user, $name);
    }

    /**
     * Determine whether the user can save the named settings document. The
     * site settings belong to no module: only administrators save them
     * (D-026), though every employee reads them for the dashboard.
     */
    public function update(User $user, string $name): Response
    {
        if ($name === Setting::SITE) {
            return $user->isAdministrator()
                ? Response::allow()
                : Response::deny('Hanya Administrator yang dapat mengubah Pengaturan Situs.');
        }

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
