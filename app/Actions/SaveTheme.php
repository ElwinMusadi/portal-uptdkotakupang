<?php

namespace App\Actions;

use App\Models\Setting;
use App\Models\ThemeRevision;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SaveTheme
{
    public function __construct(private SaveSetting $saveSetting) {}

    /**
     * Store the theme document (D-057). When it changed, keep it as a
     * revision with the administrator who saved it, and drop the revisions
     * beyond the last ThemeRevision::KEPT. Like the site settings it is not
     * logged in the per-module activity log.
     *
     * @param  array{portal: array<string, mixed>, dashboard: array<string, mixed>}  $theme
     */
    public function handle(User $user, array $theme): Setting
    {
        return DB::transaction(function () use ($user, $theme): Setting {
            $setting = $this->saveSetting->handle($user, Setting::THEME, $theme);

            if ($setting->wasRecentlyCreated || $setting->wasChanged('value')) {
                $revision = new ThemeRevision;
                $revision->forceFill(['tema' => $theme, 'user_id' => $user->getKey()])->save();

                $kept = ThemeRevision::query()->latest('id')->limit(ThemeRevision::KEPT)->pluck('id');
                ThemeRevision::query()->whereNotIn('id', $kept)->delete();
            }

            return $setting;
        });
    }
}
