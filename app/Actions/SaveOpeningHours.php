<?php

namespace App\Actions;

use App\Enums\DashboardModule;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SaveOpeningHours
{
    public function __construct(private RecordActivity $recordActivity) {}

    /**
     * Store the opening hours and log the change in the same transaction.
     * Saving the same hours again changes and logs nothing.
     *
     * @param  array{hari: list<array{hari: int, buka: bool, mulai: string, selesai: string, catatan: string}>, batasBerkasMenit: int, catatan: string, pemberitahuan: string}  $openingHours
     */
    public function handle(User $user, array $openingHours): Setting
    {
        return DB::transaction(function () use ($user, $openingHours): Setting {
            $setting = Setting::query()->lockForUpdate()->find(Setting::OPENING_HOURS)
                ?? new Setting;

            if ($setting->exists && $setting->value === $openingHours) {
                return $setting;
            }

            $setting->forceFill(['name' => Setting::OPENING_HOURS, 'value' => $openingHours])->save();
            $this->recordActivity->handle($user, DashboardModule::Jam, 'memperbarui', DashboardModule::Jam->label());

            return $setting;
        });
    }
}
