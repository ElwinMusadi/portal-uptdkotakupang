<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\SaveSetting;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateContactRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

/**
 * The Kontak & Kanal settings document as the dashboard edits it (D-019).
 * The public portal reads it through PublicContactController.
 */
class ContactController extends Controller
{
    /**
     * Show the contact document; it is empty until first saved.
     */
    public function show(): JsonResponse
    {
        return response()->json(Setting::contact());
    }

    /**
     * Replace the contact document.
     */
    public function update(UpdateContactRequest $request, SaveSetting $saveSetting): JsonResponse
    {
        $setting = $saveSetting->handle($request->user(), Setting::CONTACT, $request->contact());

        return response()->json($setting->value);
    }
}
