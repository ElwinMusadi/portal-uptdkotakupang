<?php

namespace App\Policies;

use App\Enums\DashboardModule;
use App\Models\StaticPage;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Halaman Statis (D-024): administrators and the employees assigned to the
 * module may read and edit the portal's static pages. Pages are neither
 * added nor deleted, because each one is a page of the portal.
 */
class StaticPagePolicy
{
    private const string MODULE_ONLY = 'Modul Halaman Statis belum ditugaskan kepada Anda.';

    /**
     * Determine whether the user can list the pages.
     */
    public function viewAny(User $user): Response
    {
        return $this->managesPages($user);
    }

    /**
     * Determine whether the user can read a page.
     */
    public function view(User $user, StaticPage $page): Response
    {
        return $this->managesPages($user);
    }

    /**
     * Determine whether the user can edit a page.
     */
    public function update(User $user, StaticPage $page): Response
    {
        return $this->managesPages($user);
    }

    private function managesPages(User $user): Response
    {
        return in_array(DashboardModule::Halaman->value, $user->managedModules(), true)
            ? Response::allow()
            : Response::deny(self::MODULE_ONLY);
    }
}
