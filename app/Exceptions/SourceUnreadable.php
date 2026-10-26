<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A link or document given for an AI draft cannot be read (D-085). The
 * message is shown to the employee as is, in Indonesian, under the field it
 * concerns.
 */
class SourceUnreadable extends RuntimeException
{
    /**
     * @param  'tautan'|'berkas'  $field
     */
    public function __construct(string $message, public readonly string $field = 'tautan')
    {
        parent::__construct($message);
    }
}
