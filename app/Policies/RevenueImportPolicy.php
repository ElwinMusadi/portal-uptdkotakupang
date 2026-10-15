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

    private function managesRevenue(User $user): Response
    {
        return in_array(DashboardModule::Penerimaan->value, $user->managedModules(), true)
            ? Response::allow()
            : Response::deny('Modul Laporan Penerimaan belum ditugaskan kepada Anda.');
    }
}
