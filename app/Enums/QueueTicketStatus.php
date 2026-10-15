<?php

namespace App\Enums;

/**
 * Where a queue ticket stands at its current stage (D-051).
 */
enum QueueTicketStatus: string
{
    /** Waiting at its stage. */
    case Menunggu = 'menunggu';
    /** Called to a counter at its stage. */
    case Dipanggil = 'dipanggil';
    /** Called but not at the counter; can be called again at any time. */
    case Ditunda = 'ditunda';
    /** Through every stage of its service. */
    case Selesai = 'selesai';
    /** Cancelled by staff; it goes no further. */
    case Batal = 'batal';

    /**
     * Whether the ticket goes no further.
     */
    public function isClosed(): bool
    {
        return $this === self::Selesai || $this === self::Batal;
    }
}
