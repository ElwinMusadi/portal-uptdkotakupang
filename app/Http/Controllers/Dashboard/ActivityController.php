<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActivityController extends Controller
{
    /**
     * The latest activity in the modules the user manages, newest first.
     */
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate(
            ['limit' => ['sometimes', 'integer', 'min:1', 'max:100']],
            ['limit.*' => 'Jumlah aktivitas harus 1 sampai 100.'],
        );

        $activities = Activity::query()
            ->whereIn('module', $request->user()->managedModules())
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($validated['limit'] ?? 50)
            ->get();

        return ActivityResource::collection($activities);
    }
}
