<?php

namespace App\Http\Requests\Dashboard;

/**
 * The documents to delete.
 */
class DeleteDocumentsRequest extends DeleteEntriesRequest
{
    protected function entryName(): string
    {
        return 'dokumen';
    }
}
