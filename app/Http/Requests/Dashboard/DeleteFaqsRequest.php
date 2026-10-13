<?php

namespace App\Http\Requests\Dashboard;

/**
 * The FAQ entries to delete.
 */
class DeleteFaqsRequest extends DeleteEntriesRequest
{
    protected function entryName(): string
    {
        return 'tanya jawab';
    }
}
