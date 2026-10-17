<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\ThemeRevisionResource;
use App\Models\ThemeRevision;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ThemeRevisionController extends Controller
{
    /**
     * List the last saved themes, newest first (D-057): the first is the
     * theme in use. Pengaturan › Tampilan shows who saved each one and can
     * put an earlier one back into the form.
     */
    public function __invoke(): AnonymousResourceCollection
    {
        $revisions = ThemeRevision::query()->with('user')->latest('id')->limit(ThemeRevision::KEPT)->get();

        return ThemeRevisionResource::collection($revisions);
    }
}
