<?php

namespace App\Policies;

use App\Enums\DashboardModule;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * A settings document belongs to the dashboard module of the same name
 * (D-010), e.g. "jam" to Jam Pelayanan: administrators and the employees
 * assigned to that module may read and save it. The site settings (D-026)
 * and the themes (D-057), which belong to no module, are the exception.
 */
class SettingPolicy
{
    /**
     * The documents of no module, with the message that refuses to save them
     * for anyone but an administrator.
     *
     * @var array<string, string>
     */
    private const array ADMINISTRATOR_DOCUMENTS = [
        Setting::SITE => 'Hanya Administrator yang dapat mengubah Pengaturan Situs.',
        Setting::THEME => 'Hanya Administrator yang dapat mengubah Tampilan.',
    ];

    /**
     * Determine whether the user can read the named settings document. Every
     * employee reads the site settings and the themes for the dashboard.
     */
    public function view(User $user, string $name): Response
    {
        if (array_key_exists($name, self::ADMINISTRATOR_DOCUMENTS)) {
            return Response::allow();
        }

        return $this->managesSetting($user, $name);
    }

    /**
     * Determine whether the user can save the named settings document. The
     * site settings and the themes belong to no module: only administrators
     * save them (D-026, D-057), though every employee reads them.
     */
    public function update(User $user, string $name): Response
    {
        if (array_key_exists($name, self::ADMINISTRATOR_DOCUMENTS)) {
            return $user->isAdministrator()
                ? Response::allow()
                : Response::deny(self::ADMINISTRATOR_DOCUMENTS[$name]);
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
