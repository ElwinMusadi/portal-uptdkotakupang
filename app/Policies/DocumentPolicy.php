<?php

namespace App\Policies;

use App\Enums\DashboardModule;
use App\Models\Document;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Documents belong to the Regulasi & Unduhan module (D-010). As for
 * services (D-022, D-030), its managers prepare, submit and review them;
 * whatever reaches or awaits the portal is for administrators: changing a
 * document awaiting approval or published (which includes approving,
 * returning from approval and withdrawing), and deleting. The portal sorts
 * documents by category, so there is no reorder endpoint.
 */
class DocumentPolicy
{
    private const string MODULE_ONLY = 'Modul Regulasi & Unduhan belum ditugaskan kepada Anda.';

    /**
     * Determine whether the user can list the documents. Managers of the
     * update schedule (Jadwal Pemutakhiran) may too, to see when the
     * documents last changed.
     */
    public function viewAny(User $user): Response
    {
        return in_array(DashboardModule::Pemutakhiran->value, $user->managedModules(), true)
            ? Response::allow()
            : $this->managesDocuments($user);
    }

    /**
     * Determine whether the user can read a document.
     */
    public function view(User $user, Document $document): Response
    {
        return $this->managesDocuments($user);
    }

    /**
     * Determine whether the user can add documents, which start as drafts.
     */
    public function create(User $user): Response
    {
        return $this->managesDocuments($user);
    }

    /**
     * Determine whether the user can change a document or take a workflow
     * step from its status.
     */
    public function update(User $user, Document $document): Response
    {
        if ($user->isAdministrator()) {
            return Response::allow();
        }

        $manages = $this->managesDocuments($user);

        return $manages->allowed() && $document->status->isLocked()
            ? Response::deny('Dokumen yang menunggu persetujuan atau sudah terbit hanya dapat diubah Administrator.')
            : $manages;
    }

    /**
     * Determine whether the user can delete documents.
     */
    public function deleteAny(User $user): Response
    {
        return $user->isAdministrator()
            ? Response::allow()
            : Response::deny('Hanya Administrator yang dapat menghapus dokumen.');
    }

    private function managesDocuments(User $user): Response
    {
        return in_array(DashboardModule::Dokumen->value, $user->managedModules(), true)
            ? Response::allow()
            : Response::deny(self::MODULE_ONLY);
    }
}
