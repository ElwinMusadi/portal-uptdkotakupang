<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\DeleteEntries;
use App\Actions\SyncUploads;
use App\Enums\DashboardModule;
use App\Enums\UploadTarget;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\DeleteDocumentsRequest;
use App\Models\Document;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;

class DocumentDeletionController extends Controller
{
    /**
     * Delete the listed documents and their files; only administrators may
     * (DocumentPolicy).
     */
    public function store(DeleteDocumentsRequest $request, DeleteEntries $deleteEntries, SyncUploads $syncUploads): Response
    {
        $deleteEntries->handle(
            $request->user(),
            DashboardModule::Dokumen,
            Document::class,
            $request->ids(),
            fn (Document $document): string => $document->judul,
            fn (Collection $documents) => $syncUploads->handle(UploadTarget::Dokumen, $documents->pluck('berkas.url')->all(), []),
        );

        return response()->noContent();
    }
}
