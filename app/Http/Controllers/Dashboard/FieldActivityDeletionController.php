<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\SyncUploads;
use App\Enums\UploadTarget;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\DeleteFieldActivitiesRequest;
use App\Models\FieldActivity;
use App\Models\FieldAttendance;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class FieldActivityDeletionController extends Controller
{
    /**
     * Delete the listed activities with their clock-ins and selfies; only
     * administrators may (FieldActivityPolicy). Activities that no longer
     * exist are skipped.
     */
    public function store(DeleteFieldActivitiesRequest $request, SyncUploads $syncUploads): Response
    {
        DB::transaction(function () use ($request, $syncUploads): void {
            $selfies = FieldAttendance::query()->whereIn('field_activity_id', $request->ids())->pluck('foto')->all();
            FieldActivity::query()->whereKey($request->ids())->delete();
            $syncUploads->handle(UploadTarget::Absensi, $selfies, []);
        });

        return response()->noContent();
    }
}
