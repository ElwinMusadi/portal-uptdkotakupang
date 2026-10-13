<?php

namespace App\Http\Requests\Dashboard;

/**
 * The services to delete.
 */
class DeleteServicesRequest extends DeleteEntriesRequest
{
    protected function entryName(): string
    {
        return 'layanan';
    }
}
