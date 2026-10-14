<?php

namespace App\Http\Requests\Dashboard;

/**
 * The articles to delete.
 */
class DeleteArticlesRequest extends DeleteEntriesRequest
{
    protected function entryName(): string
    {
        return 'tulisan';
    }
}
