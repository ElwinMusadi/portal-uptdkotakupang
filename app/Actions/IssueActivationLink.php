<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\URL;

class IssueActivationLink
{
    /**
     * Minutes an activation link stays valid.
     */
    public const int MINUTES = 60;

    /**
     * Build the signed link with which the owner of a pending account chooses
     * a password. The link carries a key derived from the account's current
     * password hash, so it stops working once the password changes: after
     * activation, or when a newer link replaces it.
     */
    public function handle(User $user): string
    {
        return URL::temporarySignedRoute('aktivasi.show', now()->addMinutes(self::MINUTES), [
            'user' => $user,
            'kunci' => $user->activationKey(),
        ]);
    }
}
