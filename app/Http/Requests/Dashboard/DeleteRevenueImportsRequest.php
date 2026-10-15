<?php

namespace App\Http\Requests\Dashboard;

/**
 * The STS imports to remove, with their transactions.
 */
class DeleteRevenueImportsRequest extends DeleteEntriesRequest
{
    protected function entryName(): string
    {
        return 'impor';
    }
}
