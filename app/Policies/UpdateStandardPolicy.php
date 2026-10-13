<?php

namespace App\Policies;

use App\Enums\DashboardModule;
use App\Models\UpdateStandard;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Jadwal Pemutakhiran (D-025). Every employee follows the standards of the
 * modules they manage on the Ringkasan page, so every employee may list
 * standards; the list holds only those (D-010). Administrators and the
 * employees assigned to the module follow every standard and may add, edit,
 * reorder and delete them; the standards are internal data, not portal
 * content.
 */
class UpdateStandardPolicy
{
    private const string MODULE_ONLY = 'Modul Jadwal Pemutakhiran belum ditugaskan kepada Anda.';

    /**
     * Determine whether the user can list the standards they follow.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can read a standard: managers of Jadwal
     * Pemutakhiran, or of the standard's module.
     */
    public function view(User $user, UpdateStandard $standard): Response
    {
        return in_array($standard->modul->value, $user->managedModules(), true)
            ? Response::allow()
            : $this->managesSchedule($user);
    }

    /**
     * Determine whether the user can add standards.
     */
    public function create(User $user): Response
    {
        return $this->managesSchedule($user);
    }

    /**
     * Determine whether the user can edit a standard.
     */
    public function update(User $user, UpdateStandard $standard): Response
    {
        return $this->managesSchedule($user);
    }

    /**
     * Determine whether the user can change the order of the standards.
     */
    public function reorder(User $user): Response
    {
        return $this->managesSchedule($user);
    }

    /**
     * Determine whether the user can delete standards.
     */
    public function deleteAny(User $user): Response
    {
        return $this->managesSchedule($user);
    }

    private function managesSchedule(User $user): Response
    {
        return in_array(DashboardModule::Pemutakhiran->value, $user->managedModules(), true)
            ? Response::allow()
            : Response::deny(self::MODULE_ONLY);
    }
}
