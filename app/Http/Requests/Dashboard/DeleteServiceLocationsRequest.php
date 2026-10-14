<?php

namespace App\Http\Requests\Dashboard;

/**
 * The places to remove from the list of places.
 */
class DeleteServiceLocationsRequest extends DeleteEntriesRequest
{
    protected function entryName(): string
    {
        return 'lokasi';
    }
}
