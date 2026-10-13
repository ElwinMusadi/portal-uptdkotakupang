<?php

namespace App\Http\Requests\Dashboard;

/**
 * The Samsat Keliling stops to delete.
 */
class DeleteMobileServiceStopsRequest extends DeleteEntriesRequest
{
    protected function entryName(): string
    {
        return 'jadwal keliling';
    }
}
