<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\RecordActivity;
use App\Actions\SyncUploads;
use App\Actions\UpdateWorkflowEntry;
use App\Enums\DashboardModule;
use App\Enums\UploadTarget;
use App\Enums\WorkflowStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreDocumentationRequest;
use App\Http\Requests\Dashboard\UpdateDocumentationRequest;
use App\Http\Resources\DocumentationResource;
use App\Models\Documentation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Dokumentasi Kegiatan as the dashboard manages it (D-032). The public
 * portal reads only published activities through
 * PublicDocumentationController.
 */
class DocumentationController extends Controller
{
    /**
     * List every activity, newest first.
     */
    public function index(): AnonymousResourceCollection
    {
        return DocumentationResource::collection(Documentation::query()->orderByDesc('tanggal')->orderByDesc('id')->get());
    }

    /**
     * Show one activity.
     */
    public function show(Documentation $documentation): DocumentationResource
    {
        return new DocumentationResource($documentation);
    }

    /**
     * Add an activity as a draft, prepared by the signed-in employee. The
     * activity, its activity log entry and its photos' use are saved
     * together.
     */
    public function store(StoreDocumentationRequest $request, RecordActivity $recordActivity, SyncUploads $syncUploads): JsonResponse
    {
        $documentation = DB::transaction(function () use ($request, $recordActivity, $syncUploads): Documentation {
            $documentation = new Documentation($request->content());
            $documentation->forceFill(['terbit_pada' => null, 'riwayat' => []]);
            $documentation->moveTo(WorkflowStatus::Draft, $request->user()->name, 'menyiapkan draf');
            $documentation->save();
            $recordActivity->handle($request->user(), DashboardModule::Dokumentasi, 'menambahkan', $documentation->judul);
            $syncUploads->handle(UploadTarget::Dokumentasi, [], $documentation->photoUrls());

            return $documentation;
        });

        return (new DocumentationResource($documentation))->response()->setStatusCode(201);
    }

    /**
     * Change an activity, take a workflow step, or both
     * (UpdateWorkflowEntry). Photos that were removed are deleted with the
     * change.
     */
    public function update(UpdateDocumentationRequest $request, Documentation $documentation, UpdateWorkflowEntry $updateEntry, SyncUploads $syncUploads): DocumentationResource
    {
        $before = $documentation->photoUrls();

        return new DocumentationResource($updateEntry->handle(
            $request->user(),
            $documentation,
            DashboardModule::Dokumentasi,
            'judul',
            $request->content(),
            $request->nextStatus(),
            $request->revisionNote(),
            fn (Documentation $documentation) => $syncUploads->handle(UploadTarget::Dokumentasi, $before, $documentation->photoUrls()),
        ));
    }
}
