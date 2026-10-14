<?php

namespace App\Policies;

use App\Enums\DashboardModule;
use App\Models\Documentation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Activities belong to the Dokumentasi Kegiatan module (D-010). As for news
 * (D-031, D-032), its managers take every workflow step themselves,
 * including approving, publishing and withdrawing, and change published
 * activities. Only administrators delete.
 */
class DocumentationPolicy
{
    private const string MODULE_ONLY = 'Modul Dokumentasi Kegiatan belum ditugaskan kepada Anda.';

    /**
     * Determine whether the user can list the activities. Managers of the
     * update schedule (Jadwal Pemutakhiran) may too, to see when the
     * documentation last changed.
     */
    public function viewAny(User $user): Response
    {
        return in_array(DashboardModule::Pemutakhiran->value, $user->managedModules(), true)
            ? Response::allow()
            : $this->managesDocumentation($user);
    }

    /**
     * Determine whether the user can read an activity.
     */
    public function view(User $user, Documentation $documentation): Response
    {
        return $this->managesDocumentation($user);
    }

    /**
     * Determine whether the user can add activities, which start as drafts.
     */
    public function create(User $user): Response
    {
        return $this->managesDocumentation($user);
    }

    /**
     * Determine whether the user can change an activity or take a workflow
     * step from its status.
     */
    public function update(User $user, Documentation $documentation): Response
    {
        return $this->managesDocumentation($user);
    }

    /**
     * Determine whether the user can delete activities.
     */
    public function deleteAny(User $user): Response
    {
        return $user->isAdministrator()
            ? Response::allow()
            : Response::deny('Hanya Administrator yang dapat menghapus dokumentasi.');
    }

    private function managesDocumentation(User $user): Response
    {
        return in_array(DashboardModule::Dokumentasi->value, $user->managedModules(), true)
            ? Response::allow()
            : Response::deny(self::MODULE_ONLY);
    }
}
