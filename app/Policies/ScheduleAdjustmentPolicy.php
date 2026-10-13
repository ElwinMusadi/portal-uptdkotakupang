<?php

namespace App\Policies;

use App\Enums\DashboardModule;
use App\Models\ScheduleAdjustment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Schedule adjustments belong to the Jam Pelayanan module (D-010): the
 * administrators and employees who manage the opening hours manage them,
 * deletion included (D-014).
 */
class ScheduleAdjustmentPolicy
{
    /**
     * Determine whether the user can list the adjustments.
     */
    public function viewAny(User $user): Response
    {
        return $this->managesOpeningHours($user);
    }

    /**
     * Determine whether the user can read an adjustment.
     */
    public function view(User $user, ScheduleAdjustment $adjustment): Response
    {
        return $this->managesOpeningHours($user);
    }

    /**
     * Determine whether the user can add adjustments.
     */
    public function create(User $user): Response
    {
        return $this->managesOpeningHours($user);
    }

    /**
     * Determine whether the user can edit an adjustment.
     */
    public function update(User $user, ScheduleAdjustment $adjustment): Response
    {
        return $this->managesOpeningHours($user);
    }

    /**
     * Determine whether the user can delete adjustments.
     */
    public function deleteAny(User $user): Response
    {
        return $this->managesOpeningHours($user);
    }

    private function managesOpeningHours(User $user): Response
    {
        return in_array(DashboardModule::Jam->value, $user->managedModules(), true)
            ? Response::allow()
            : Response::deny('Modul Jam Pelayanan belum ditugaskan kepada Anda.');
    }
}
