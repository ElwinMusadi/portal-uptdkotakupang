<?php

namespace App\Http\Requests\Dashboard;

/**
 * The schedule adjustments to delete.
 */
class DeleteScheduleAdjustmentsRequest extends DeleteEntriesRequest
{
    protected function entryName(): string
    {
        return 'penyesuaian jadwal';
    }
}
