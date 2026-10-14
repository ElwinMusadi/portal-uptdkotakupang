<?php

namespace App\Http\Requests\Dashboard;

/**
 * The field activities to delete, with their clock-ins.
 */
class DeleteFieldActivitiesRequest extends DeleteEntriesRequest
{
    protected function entryName(): string
    {
        return 'kegiatan';
    }
}
