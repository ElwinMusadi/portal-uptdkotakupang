<?php

namespace App\Policies;

use App\Enums\DashboardModule;
use App\Models\Service;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Services belong to the Persyaratan Layanan module (D-010). Its managers
 * prepare, submit and review them; whatever reaches or awaits the portal is
 * for administrators (D-022): changing a service awaiting approval or
 * published (which includes approving, returning from approval and
 * withdrawing), the portal order, and deleting.
 */
class ServicePolicy
{
    private const string MODULE_ONLY = 'Modul Persyaratan Layanan belum ditugaskan kepada Anda.';

    /**
     * Determine whether the user can list the services. Managers of the
     * update schedule (Jadwal Pemutakhiran) may too, to see when the
     * requirements last changed, as for Samsat Keliling (D-013).
     */
    public function viewAny(User $user): Response
    {
        return in_array(DashboardModule::Pemutakhiran->value, $user->managedModules(), true)
            ? Response::allow()
            : $this->managesServices($user);
    }

    /**
     * Determine whether the user can read a service.
     */
    public function view(User $user, Service $service): Response
    {
        return $this->managesServices($user);
    }

    /**
     * Determine whether the user can add services, which start as drafts.
     */
    public function create(User $user): Response
    {
        return $this->managesServices($user);
    }

    /**
     * Determine whether the user can change a service or take a workflow
     * step from its status.
     */
    public function update(User $user, Service $service): Response
    {
        if ($user->isAdministrator()) {
            return Response::allow();
        }

        $manages = $this->managesServices($user);

        return $manages->allowed() && $service->status->isLocked()
            ? Response::deny('Layanan yang menunggu persetujuan atau sudah terbit hanya dapat diubah Administrator.')
            : $manages;
    }

    /**
     * Determine whether the user can change the order of the services on the
     * portal.
     */
    public function reorder(User $user): Response
    {
        return $user->isAdministrator()
            ? Response::allow()
            : Response::deny('Hanya Administrator yang dapat mengubah urutan layanan di portal.');
    }

    /**
     * Determine whether the user can delete services.
     */
    public function deleteAny(User $user): Response
    {
        return $user->isAdministrator()
            ? Response::allow()
            : Response::deny('Hanya Administrator yang dapat menghapus layanan.');
    }

    private function managesServices(User $user): Response
    {
        return in_array(DashboardModule::Layanan->value, $user->managedModules(), true)
            ? Response::allow()
            : Response::deny(self::MODULE_ONLY);
    }
}
