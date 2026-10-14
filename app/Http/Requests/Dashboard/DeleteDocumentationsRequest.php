<?php

namespace App\Http\Requests\Dashboard;

/**
 * The activities to delete.
 */
class DeleteDocumentationsRequest extends DeleteEntriesRequest
{
    protected function entryName(): string
    {
        return 'kegiatan';
    }
}
