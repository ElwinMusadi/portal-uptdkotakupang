<?php

namespace App\Policies;

use App\Enums\DashboardModule;
use App\Models\Message;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Messages belong to the Pesan & Pengaduan module (D-010): administrators
 * and the employees assigned to it read them, record messages from other
 * channels, change their status and respond. Only administrators may delete
 * them (D-016); employees close a message by marking it done.
 */
class MessagePolicy
{
    private const string MODULE_ONLY = 'Modul Pesan & Pengaduan belum ditugaskan kepada Anda.';

    /**
     * Determine whether the user can list the messages.
     */
    public function viewAny(User $user): Response
    {
        return $this->managesMessages($user);
    }

    /**
     * Determine whether the user can read a message.
     */
    public function view(User $user, Message $message): Response
    {
        return $this->managesMessages($user);
    }

    /**
     * Determine whether the user can record a message from another channel.
     */
    public function create(User $user): Response
    {
        return $this->managesMessages($user);
    }

    /**
     * Determine whether the user can change a message's status and respond.
     */
    public function update(User $user, Message $message): Response
    {
        return $this->managesMessages($user);
    }

    /**
     * Determine whether the user can delete messages.
     */
    public function deleteAny(User $user): Response
    {
        return $user->isAdministrator()
            ? Response::allow()
            : Response::deny('Hanya Administrator yang dapat menghapus pesan. Tandai selesai untuk menutupnya.');
    }

    private function managesMessages(User $user): Response
    {
        return in_array(DashboardModule::Pesan->value, $user->managedModules(), true)
            ? Response::allow()
            : Response::deny(self::MODULE_ONLY);
    }
}
