<?php

namespace App\Actions;

use App\Enums\DashboardModule;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SaveOverviewStatus
{
    public function __construct(
        private SaveSetting $saveSetting,
        private RecordActivity $recordActivity,
    ) {}

    /**
     * Open or close the Ikhtisar page at /laporan-penerimaan to anyone
     * (D-100). A change starts its "sejak" now and is logged under Laporan
     * Penerimaan, whose figures the page shows, as opening it makes them
     * public; saving the same state changes and logs nothing.
     *
     * @return array{aktif: bool, sejak: ?string}
     */
    public function handle(User $user, bool $open): array
    {
        return DB::transaction(function () use ($user, $open): array {
            $stored = Setting::overview();
            if ($stored['aktif'] === $open) {
                return $stored;
            }

            $value = $this->saveSetting->handle($user, Setting::OVERVIEW, ['aktif' => $open, 'sejak' => now()->toIso8601String()])->value;
            $this->recordActivity->handle($user, DashboardModule::Penerimaan, $open ? 'mengaktifkan' : 'menonaktifkan', 'Halaman Ikhtisar /laporan-penerimaan');

            return $value;
        });
    }
}
