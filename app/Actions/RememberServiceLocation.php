<?php

namespace App\Actions;

use App\Enums\DashboardModule;
use App\Models\ServiceLocation;
use App\Models\User;

class RememberServiceLocation
{
    public function __construct(private RecordActivity $recordActivity) {}

    /**
     * Add a place typed into a schedule entry to the list of places, unless
     * it is there already whatever its letter case (D-044), and log it.
     * Call it in the transaction that saves the entry.
     */
    public function handle(User $user, string $name, string $district): ?ServiceLocation
    {
        if ($name === '' || ServiceLocation::query()->named($name)->exists()) {
            return null;
        }

        $location = new ServiceLocation(['nama' => $name, 'kecamatan' => $district]);
        $location->urutan = (int) (ServiceLocation::query()->max('urutan') ?? -1) + 1;
        $location->save();
        $this->recordActivity->handle($user, DashboardModule::Keliling, 'menambahkan', $location->title());

        return $location;
    }
}
