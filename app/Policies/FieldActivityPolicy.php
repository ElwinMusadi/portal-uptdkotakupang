<?php

namespace App\Policies;

use App\Models\FieldActivity;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Field activities are set up by administrators: the place, the radius
 * and the officers (D-034). An officer may clock in only at the activities
 * assigned to them. Field attendance is not a dashboard module.
 */
class FieldActivityPolicy
{
    private const string ADMINISTRATORS_ONLY = 'Hanya Administrator yang dapat mengelola kegiatan lapangan.';

    /**
     * Determine whether the user can list the activities and their clock-ins.
     */
    public function viewAny(User $user): Response
    {
        return $this->administrator($user);
    }

    /**
     * Determine whether the user can read an activity and its clock-ins.
     */
    public function view(User $user, FieldActivity $fieldActivity): Response
    {
        return $this->administrator($user);
    }

    /**
     * Determine whether the user can add activities.
     */
    public function create(User $user): Response
    {
        return $this->administrator($user);
    }

    /**
     * Determine whether the user can change an activity.
     */
    public function update(User $user, FieldActivity $fieldActivity): Response
    {
        return $this->administrator($user);
    }

    /**
     * Determine whether the user can delete activities.
     */
    public function deleteAny(User $user): Response
    {
        return $this->administrator($user);
    }

    /**
     * Determine whether the user can clock in at the activity: only the
     * officers assigned to it.
     */
    public function clockIn(User $user, FieldActivity $fieldActivity): Response
    {
        return $fieldActivity->officers()->whereKey($user->id)->exists()
            ? Response::allow()
            : Response::deny('Anda tidak ditugaskan di kegiatan ini.');
    }

    private function administrator(User $user): Response
    {
        return $user->isAdministrator() ? Response::allow() : Response::deny(self::ADMINISTRATORS_ONLY);
    }
}
