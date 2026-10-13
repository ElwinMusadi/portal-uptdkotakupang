<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Account management is for administrators only. Nobody may change the
 * status of, or reset, their own account: since every actor is an active
 * administrator, at least one active administrator always remains.
 */
class UserPolicy
{
    private const string ADMINISTRATORS_ONLY = 'Hanya Administrator yang dapat mengelola akun.';

    /**
     * Determine whether the user can list all accounts.
     */
    public function viewAny(User $user): Response
    {
        return $this->administrator($user);
    }

    /**
     * Determine whether the user can create accounts.
     */
    public function create(User $user): Response
    {
        return $this->administrator($user);
    }

    /**
     * Determine whether the user can change an account's name, email or role.
     * Administrators cannot change their own role; UpdateAccountRequest
     * reports that on the role field.
     */
    public function update(User $user, User $account): Response
    {
        return $this->administrator($user);
    }

    /**
     * Determine whether the user can suspend, reactivate or deactivate the account.
     */
    public function changeStatus(User $user, User $account): Response
    {
        return $this->administratorActingOnOthers($user, $account, 'Anda tidak dapat mengubah status akun Anda sendiri.');
    }

    /**
     * Determine whether the user can issue an activation link for the account,
     * which also resets an active account's password.
     */
    public function issueActivationLink(User $user, User $account): Response
    {
        return $this->administratorActingOnOthers($user, $account, 'Anda tidak dapat mengatur ulang akun Anda sendiri.');
    }

    private function administrator(User $user): Response
    {
        return $user->isAdministrator() ? Response::allow() : Response::deny(self::ADMINISTRATORS_ONLY);
    }

    private function administratorActingOnOthers(User $user, User $account, string $ownAccountMessage): Response
    {
        if (! $user->isAdministrator()) {
            return Response::deny(self::ADMINISTRATORS_ONLY);
        }

        return $user->is($account) ? Response::deny($ownAccountMessage) : Response::allow();
    }
}
