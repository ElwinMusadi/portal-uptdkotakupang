<?php

namespace App\Policies;

use App\Enums\DashboardModule;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Laporan Penerimaan belongs to its own module (D-047): administrators and the
 * employees assigned to it import STS files, read the report and delete
 * imports made by mistake.
 */
class RevenueImportPolicy
{
    /**
     * Determine whether the user can read the imports and the report.
     */
    public function viewAny(User $user): Response
    {
        return $this->managesRevenue($user);
    }

    /**
     * Determine whether the user can import STS files.
     */
    public function create(User $user): Response
    {
        return $this->managesRevenue($user);
    }

    /**
     * Determine whether the user can delete imports and their transactions.
     */
    public function deleteAny(User $user): Response
    {
        return $this->managesRevenue($user);
    }

    /**
     * Determine whether the user can set a year's revenue target and opening
     * balance for the realisation report: everyone assigned to the module
     * (D-068).
     */
    public function updateTargets(User $user): Response
    {
        return $this->managesRevenue($user);
    }

    /**
     * Determine whether the user can show imports on the portal or take them
     * off: Administrators only, once the import is checked (D-048).
     */
    public function publish(User $user): Response
    {
        return $user->isAdministrator()
            ? Response::allow()
            : Response::deny('Hanya Administrator yang dapat menampilkan laporan penerimaan di portal.');
    }

    private function managesRevenue(User $user): Response
    {
        return in_array(DashboardModule::Penerimaan->value, $user->managedModules(), true)
            ? Response::allow()
            : Response::deny('Modul Laporan Penerimaan belum ditugaskan kepada Anda.');
    }
}
