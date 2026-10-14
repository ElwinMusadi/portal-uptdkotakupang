<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\DeleteEntries;
use App\Actions\SyncUploads;
use App\Enums\DashboardModule;
use App\Enums\UploadTarget;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\DeleteDocumentationsRequest;
use App\Models\Documentation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;

class DocumentationDeletionController extends Controller
{
    /**
     * Delete the listed activities and their photos; only administrators
     * may (DocumentationPolicy).
     */
    public function store(DeleteDocumentationsRequest $request, DeleteEntries $deleteEntries, SyncUploads $syncUploads): Response
    {
        $deleteEntries->handle(
            $request->user(),
            DashboardModule::Dokumentasi,
            Documentation::class,
            $request->ids(),
            fn (Documentation $documentation): string => $documentation->judul,
            fn (Collection $activities) => $syncUploads->handle(UploadTarget::Dokumentasi, $activities->flatMap->photoUrls()->all(), []),
        );

        return response()->noContent();
    }
}
