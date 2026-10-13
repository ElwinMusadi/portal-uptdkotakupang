<?php

namespace App\Http\Requests\Dashboard;

/**
 * The update standards to delete.
 */
class DeleteUpdateStandardsRequest extends DeleteEntriesRequest
{
    protected function entryName(): string
    {
        return 'standar pemutakhiran';
    }
}
