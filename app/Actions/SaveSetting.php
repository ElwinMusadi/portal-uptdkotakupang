<?php

namespace App\Actions;

use App\Enums\DashboardModule;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SaveSetting
{
    public function __construct(private RecordActivity $recordActivity) {}

    /**
     * Store a settings document and log the change in the same transaction,
     * e.g. "memperbarui Jam Pelayanan". Saving a document equal to the stored
     * one changes and logs nothing; keys in $ignored, such as a "diperbarui"
     * stamp, do not count as a change. A document of no dashboard module,
     * such as the site settings, is not logged: the activity log is kept per
     * module (D-026).
     *
     * @param  array<string, mixed>  $value
     * @param  list<string>  $ignored
     */
    public function handle(User $user, string $name, array $value, array $ignored = []): Setting
    {
        return DB::transaction(function () use ($user, $name, $value, $ignored): Setting {
            $setting = Setting::query()->lockForUpdate()->find($name) ?? new Setting;

            if ($setting->exists && Arr::except($setting->value, $ignored) === Arr::except($value, $ignored)) {
                return $setting;
            }

            $setting->forceFill(['name' => $name, 'value' => $value])->save();

            $module = DashboardModule::tryFrom($name);

            if ($module !== null) {
                $this->recordActivity->handle($user, $module, 'memperbarui', $module->label());
            }

            return $setting;
        });
    }
}
