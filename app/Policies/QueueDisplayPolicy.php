<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Waiting-room TV displays (D-051) show plate numbers, so only
 * administrators set them up and revoke them.
 */
class QueueDisplayPolicy
{
    /**
     * Determine whether the user can list, set up and revoke displays.
     */
    public function manage(User $user): Response
    {
        return $user->isAdministrator()
            ? Response::allow()
            : Response::deny('Hanya Administrator yang dapat mengatur layar antrean.');
    }
}
