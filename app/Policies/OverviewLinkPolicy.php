<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * The links to the Ikhtisar page (D-099) open the revenue and Pemetaan PKB
 * figures to whoever holds them, so only administrators make and revoke
 * them.
 */
class OverviewLinkPolicy
{
    /**
     * Determine whether the user can list, make and revoke links.
     */
    public function manage(User $user): Response
    {
        return $user->isAdministrator()
            ? Response::allow()
            : Response::deny('Hanya Administrator yang dapat mengatur tautan ikhtisar.');
    }
}
