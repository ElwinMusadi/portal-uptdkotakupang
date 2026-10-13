<?php

namespace App\Enums;

/**
 * Account lifecycle. Only active accounts may sign in or keep a session; a
 * new account stays pending until its owner chooses a password through an
 * activation link.
 */
enum AccountStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Deactivated = 'deactivated';

    /**
     * Whether an administrator may move an account from this status to the
     * given one directly. Pending is reached only by issuing an activation
     * link, and a pending or deactivated account becomes active only when
     * its owner uses one.
     */
    public function canChangeTo(self $status): bool
    {
        $allowed = match ($this) {
            self::Pending => [self::Deactivated],
            self::Active => [self::Suspended, self::Deactivated],
            self::Suspended => [self::Active, self::Deactivated],
            self::Deactivated => [],
        };

        return in_array($status, $allowed, true);
    }
}
