<?php

namespace App\Enums;

/**
 * Where a queue ticket stands at its current stage (D-051).
 */
enum QueueTicketStatus: string
{
    /** Waiting at its stage. */
    case Menunggu = 'menunggu';
    /** Cancelled by staff; it goes no further. */
    case Batal = 'batal';
}
