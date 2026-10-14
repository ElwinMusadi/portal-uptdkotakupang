<?php

namespace App\Policies;

use App\Enums\DashboardModule;
use App\Models\MobileServiceStop;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Service outside the office (Samsat Keliling stops, fixed outlets and
 * temporary counters, D-043) belongs to the module once named Samsat
 * Keliling, now Layanan Luar Kantor (D-010): administrators and the
 * employees assigned to it manage the entries, deletion included (D-013). Managers of Jadwal Pemutakhiran may also list them,
 * read-only, because they monitor when every module was last updated.
 */
class MobileServiceStopPolicy
{
    private const string MODULE_ONLY = 'Modul Layanan Luar Kantor belum ditugaskan kepada Anda.';

    /**
     * Determine whether the user can list the stops.
     */
    public function viewAny(User $user): Response
    {
        return array_intersect([DashboardModule::Keliling->value, DashboardModule::Pemutakhiran->value], $user->managedModules()) !== []
            ? Response::allow()
            : Response::deny(self::MODULE_ONLY);
    }

    /**
     * Determine whether the user can read a stop.
     */
    public function view(User $user, MobileServiceStop $stop): Response
    {
        return $this->managesKeliling($user);
    }

    /**
     * Determine whether the user can add stops.
     */
    public function create(User $user): Response
    {
        return $this->managesKeliling($user);
    }

    /**
     * Determine whether the user can edit a stop.
     */
    public function update(User $user, MobileServiceStop $stop): Response
    {
        return $this->managesKeliling($user);
    }

    /**
     * Determine whether the user can delete stops.
     */
    public function deleteAny(User $user): Response
    {
        return $this->managesKeliling($user);
    }

    private function managesKeliling(User $user): Response
    {
        return in_array(DashboardModule::Keliling->value, $user->managedModules(), true)
            ? Response::allow()
            : Response::deny(self::MODULE_ONLY);
    }
}
