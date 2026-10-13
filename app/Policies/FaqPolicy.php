<?php

namespace App\Policies;

use App\Enums\DashboardModule;
use App\Models\Faq;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * FAQ entries in the dashboard belong to the Tanya Jawab module (D-010):
 * administrators and the employees assigned to it may read, add, edit and
 * reorder them. Only administrators may delete; employees hide an entry by
 * switching off its display instead (D-011).
 */
class FaqPolicy
{
    private const string MODULE_ONLY = 'Modul Tanya Jawab belum ditugaskan kepada Anda.';

    /**
     * Determine whether the user can list the entries.
     */
    public function viewAny(User $user): Response
    {
        return $this->managesFaq($user);
    }

    /**
     * Determine whether the user can read an entry.
     */
    public function view(User $user, Faq $faq): Response
    {
        return $this->managesFaq($user);
    }

    /**
     * Determine whether the user can add entries.
     */
    public function create(User $user): Response
    {
        return $this->managesFaq($user);
    }

    /**
     * Determine whether the user can edit an entry.
     */
    public function update(User $user, Faq $faq): Response
    {
        return $this->managesFaq($user);
    }

    /**
     * Determine whether the user can change the order of the entries.
     */
    public function reorder(User $user): Response
    {
        return $this->managesFaq($user);
    }

    /**
     * Determine whether the user can delete entries.
     */
    public function deleteAny(User $user): Response
    {
        return $user->isAdministrator()
            ? Response::allow()
            : Response::deny('Hanya Administrator yang dapat menghapus tanya jawab. Matikan "Tampilkan di portal" untuk menyembunyikannya.');
    }

    private function managesFaq(User $user): Response
    {
        return in_array(DashboardModule::Faq->value, $user->managedModules(), true)
            ? Response::allow()
            : Response::deny(self::MODULE_ONLY);
    }
}
