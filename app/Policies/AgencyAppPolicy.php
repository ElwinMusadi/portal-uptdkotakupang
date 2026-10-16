<?php

namespace App\Policies;

use App\Enums\DashboardModule;
use App\Models\AgencyApp;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Apps belong to the Aplikasi & Situs module (D-010, D-054). Its managers
 * add, change, reorder and delete apps, and changes reach the portal when
 * saved.
 */
class AgencyAppPolicy
{
    private const string MODULE_ONLY = 'Modul Aplikasi & Situs belum ditugaskan kepada Anda.';

    /**
     * Determine whether the user can list the apps.
     */
    public function viewAny(User $user): Response
    {
        return $this->managesApps($user);
    }

    /**
     * Determine whether the user can read an app.
     */
    public function view(User $user, AgencyApp $agencyApp): Response
    {
        return $this->managesApps($user);
    }

    /**
     * Determine whether the user can add apps.
     */
    public function create(User $user): Response
    {
        return $this->managesApps($user);
    }

    /**
     * Determine whether the user can change an app.
     */
    public function update(User $user, AgencyApp $agencyApp): Response
    {
        return $this->managesApps($user);
    }

    /**
     * Determine whether the user can change the order of the apps.
     */
    public function reorder(User $user): Response
    {
        return $this->managesApps($user);
    }

    /**
     * Determine whether the user can delete apps.
     */
    public function deleteAny(User $user): Response
    {
        return $this->managesApps($user);
    }

    private function managesApps(User $user): Response
    {
        return in_array(DashboardModule::Aplikasi->value, $user->managedModules(), true)
            ? Response::allow()
            : Response::deny(self::MODULE_ONLY);
    }
}
