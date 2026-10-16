<?php

namespace App\Http\Requests\Dashboard;

/**
 * The apps to delete.
 */
class DeleteAgencyAppsRequest extends DeleteEntriesRequest
{
    protected function entryName(): string
    {
        return 'aplikasi';
    }
}
