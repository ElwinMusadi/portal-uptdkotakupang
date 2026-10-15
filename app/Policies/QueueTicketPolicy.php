<?php

namespace App\Policies;

use App\Enums\DashboardModule;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Antrean Online belongs to its own module (D-051): administrators and the
 * employees assigned to it print, list and cancel queue numbers, each with
 * their own account.
 */
class QueueTicketPolicy
{
    /**
     * Determine whether the user can list the queue numbers.
     */
    public function viewAny(User $user): Response
    {
        return $this->managesQueue($user);
    }

    /**
     * Determine whether the user can print queue numbers.
     */
    public function create(User $user): Response
    {
        return $this->managesQueue($user);
    }

    /**
     * Determine whether the user can call numbers at a counter and finish
     * their stage.
     */
    public function call(User $user): Response
    {
        return $this->managesQueue($user);
    }

    /**
     * Determine whether the user can mark numbers at a station and undo
     * their marking.
     */
    public function mark(User $user): Response
    {
        return $this->managesQueue($user);
    }

    /**
     * Determine whether the user can cancel queue numbers.
     */
    public function cancel(User $user): Response
    {
        return $this->managesQueue($user);
    }

    private function managesQueue(User $user): Response
    {
        return in_array(DashboardModule::Antrean->value, $user->managedModules(), true)
            ? Response::allow()
            : Response::deny('Modul Antrean Online belum ditugaskan kepada Anda.');
    }
}
