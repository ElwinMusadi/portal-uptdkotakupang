<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SettingTimestampController extends Controller
{
    /**
     * When each settings document stored on the server was last saved, e.g.
     * {"jam": "2026-10-13T08:00:00+00:00"}. Documents never saved are left
     * out. Every signed-in employee may read it, for the update schedule.
     */
    public function __invoke(): JsonResponse
    {
        $timestamps = Setting::query()->get(['name', 'updated_at'])
            ->mapWithKeys(fn (Setting $setting): array => [$setting->name => $setting->updated_at?->toIso8601String()]);

        return response()->json((object) $timestamps->all());
    }
}
