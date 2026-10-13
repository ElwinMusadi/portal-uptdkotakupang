<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\RecordActivity;
use App\Actions\SyncUploads;
use App\Actions\UpdateWorkflowEntry;
use App\Enums\DashboardModule;
use App\Enums\UploadTarget;
use App\Enums\WorkflowStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreDocumentRequest;
use App\Http\Requests\Dashboard\UpdateDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Regulasi & Unduhan as the dashboard manages it (D-030). The public portal
 * reads only published documents through PublicDocumentController.
 */
class DocumentController extends Controller
{
    /**
     * List every document in the order it was added.
     */
    public function index(): AnonymousResourceCollection
    {
        return DocumentResource::collection(Document::query()->orderBy('urutan')->orderBy('id')->get());
    }

    /**
     * Show one document.
     */
    public function show(Document $document): DocumentResource
    {
        return new DocumentResource($document);
    }

    /**
     * Add a document as a draft, prepared by the signed-in employee. The
     * document, its activity log entry and its file's use are saved
     * together.
     */
    public function store(StoreDocumentRequest $request, RecordActivity $recordActivity, SyncUploads $syncUploads): JsonResponse
    {
        $document = DB::transaction(function () use ($request, $recordActivity, $syncUploads): Document {
            $document = new Document($request->content());
            $document->forceFill([
                'urutan' => (int) (Document::query()->max('urutan') ?? -1) + 1,
                'terbit_pada' => null,
                'riwayat' => [],
            ]);
            $document->moveTo(WorkflowStatus::Draft, $request->user()->name, 'menyiapkan draf');
            $document->save();
            $recordActivity->handle($request->user(), DashboardModule::Dokumen, 'menambahkan', $document->judul);
            $syncUploads->handle(UploadTarget::Dokumen, [], [$document->berkas['url'] ?? null]);

            return $document;
        });

        return (new DocumentResource($document))->response()->setStatusCode(201);
    }

    /**
     * Change a document, take a workflow step, or both (UpdateWorkflowEntry).
     * A replaced or removed file is deleted with the change.
     */
    public function update(UpdateDocumentRequest $request, Document $document, UpdateWorkflowEntry $updateEntry, SyncUploads $syncUploads): DocumentResource
    {
        $before = $document->berkas['url'] ?? null;

        return new DocumentResource($updateEntry->handle(
            $request->user(),
            $document,
            DashboardModule::Dokumen,
            'judul',
            $request->content(),
            $request->nextStatus(),
            $request->revisionNote(),
            fn (Document $document) => $syncUploads->handle(UploadTarget::Dokumen, [$before], [$document->berkas['url'] ?? null]),
        ));
    }
}
