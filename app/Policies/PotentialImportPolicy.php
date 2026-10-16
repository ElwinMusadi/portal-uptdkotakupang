<?php

namespace App\Policies;

use App\Enums\DashboardModule;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Pemetaan PKB belongs to its own module (D-052): administrators and the
 * employees assigned to it import BPAD's potential-tax file, read the map
 * and the collection lists with the owners' identities. Only administrators
 * delete the data.
 */
class PotentialImportPolicy
{
    /**
     * Determine whether the user can read the data, identities included.
     */
    public function viewAny(User $user): Response
    {
        return $this->managesMapping($user);
    }

    /**
     * Determine whether the user can import the file.
     */
    public function create(User $user): Response
    {
        return $this->managesMapping($user);
    }

    /**
     * Determine whether the user can delete the data.
     */
    public function delete(User $user): Response
    {
        return $user->isAdministrator()
            ? Response::allow()
            : Response::deny('Hanya Administrator yang dapat menghapus data potensi PKB.');
    }

    /**
     * Determine whether the user can show the figures on the public portal
     * or take them off: Administrators only, once the data is checked.
     */
    public function publish(User $user): Response
    {
        return $user->isAdministrator()
            ? Response::allow()
            : Response::deny('Hanya Administrator yang dapat menampilkan pemetaan PKB di portal.');
    }

    /**
     * Determine whether the user can choose the columns of the Pemetaan PKB
     * tables, which the portal follows too: Administrators only.
     */
    public function arrangeColumns(User $user): Response
    {
        return $user->isAdministrator()
            ? Response::allow()
            : Response::deny('Hanya Administrator yang dapat mengatur kolom tabel pemetaan PKB.');
    }

    private function managesMapping(User $user): Response
    {
        return in_array(DashboardModule::Pemetaan->value, $user->managedModules(), true)
            ? Response::allow()
            : Response::deny('Modul Pemetaan PKB belum ditugaskan kepada Anda.');
    }
}
