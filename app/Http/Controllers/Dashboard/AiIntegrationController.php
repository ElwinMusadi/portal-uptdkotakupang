<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\SaveSetting;
use App\Actions\TestAiConnection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\TestAiIntegrationRequest;
use App\Http\Requests\Dashboard\UpdateAiIntegrationRequest;
use App\Models\Setting;
use App\Support\AiSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Pengaturan › Integrasi AI (D-085), for administrators only: the AI API that
 * writes article drafts. The key is written, never read back.
 */
class AiIntegrationController extends Controller
{
    /**
     * Show the settings in use, with only the last four characters of the key.
     */
    public function show(): JsonResponse
    {
        return response()->json(AiSettings::describe());
    }

    /**
     * Save the settings. Like the site settings they are of no module and are
     * not in the activity log; the application log notes who saved them and
     * whether the key changed, never the key.
     */
    public function update(UpdateAiIntegrationRequest $request, SaveSetting $saveSetting): JsonResponse
    {
        $saveSetting->handle($request->user(), Setting::AI_INTEGRATION, $request->aiIntegration());

        Log::info('AI integration settings saved', [
            'user_id' => $request->user()->getKey(),
            'key_changed' => $request->newKey() !== null || $request->boolean('hapusKunci'),
        ]);

        return response()->json(AiSettings::describe());
    }

    /**
     * Test the values in the form, saved or not.
     */
    public function test(TestAiIntegrationRequest $request, TestAiConnection $testAiConnection): JsonResponse
    {
        return response()->json($testAiConnection->handle($request->connection()));
    }
}
