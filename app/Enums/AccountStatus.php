<?php

namespace App\Enums;

/**
 * Account lifecycle. Only active accounts may sign in or keep a session;
 * new accounts start as pending until an administrator activates them.
 */
enum AccountStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Deactivated = 'deactivated';
}
