<?php

namespace App\Http\Requests\Dashboard;

/**
 * The messages to delete.
 */
class DeleteMessagesRequest extends DeleteEntriesRequest
{
    protected function entryName(): string
    {
        return 'pesan';
    }
}
