<?php

namespace App\Policies;

use App\Enums\DashboardModule;
use App\Models\OrganizationUnit;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Units belong to the Struktur Organisasi module (D-010). Its managers add,
 * change, reorder and delete units, and changes reach the portal when saved
 * (D-033).
 */
class OrganizationUnitPolicy
{
    private const string MODULE_ONLY = 'Modul Struktur Organisasi belum ditugaskan kepada Anda.';

    /**
     * Determine whether the user can list the units.
     */
    public function viewAny(User $user): Response
    {
        return $this->managesStructure($user);
    }

    /**
     * Determine whether the user can read a unit.
     */
    public function view(User $user, OrganizationUnit $organizationUnit): Response
    {
        return $this->managesStructure($user);
    }

    /**
     * Determine whether the user can add units.
     */
    public function create(User $user): Response
    {
        return $this->managesStructure($user);
    }

    /**
     * Determine whether the user can change a unit.
     */
    public function update(User $user, OrganizationUnit $organizationUnit): Response
    {
        return $this->managesStructure($user);
    }

    /**
     * Determine whether the user can change the order of the units.
     */
    public function reorder(User $user): Response
    {
        return $this->managesStructure($user);
    }

    /**
     * Determine whether the user can delete units.
     */
    public function deleteAny(User $user): Response
    {
        return $this->managesStructure($user);
    }

    private function managesStructure(User $user): Response
    {
        return in_array(DashboardModule::Unit->value, $user->managedModules(), true)
            ? Response::allow()
            : Response::deny(self::MODULE_ONLY);
    }
}
