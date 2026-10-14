<?php

namespace App\Policies;

use App\Enums\DashboardModule;
use App\Models\ServiceLocation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * The list of places belongs to the Layanan Luar Kantor module (D-044):
 * administrators and the employees assigned to it read and manage it.
 */
class ServiceLocationPolicy
{
    /**
     * Determine whether the user can list the places.
     */
    public function viewAny(User $user): Response
    {
        return $this->managesKeliling($user);
    }

    /**
     * Determine whether the user can read a place.
     */
    public function view(User $user, ServiceLocation $location): Response
    {
        return $this->managesKeliling($user);
    }

    /**
     * Determine whether the user can add places.
     */
    public function create(User $user): Response
    {
        return $this->managesKeliling($user);
    }

    /**
     * Determine whether the user can edit a place.
     */
    public function update(User $user, ServiceLocation $location): Response
    {
        return $this->managesKeliling($user);
    }

    /**
     * Determine whether the user can delete places.
     */
    public function deleteAny(User $user): Response
    {
        return $this->managesKeliling($user);
    }

    private function managesKeliling(User $user): Response
    {
        return in_array(DashboardModule::Keliling->value, $user->managedModules(), true)
            ? Response::allow()
            : Response::deny('Modul Layanan Luar Kantor belum ditugaskan kepada Anda.');
    }
}
