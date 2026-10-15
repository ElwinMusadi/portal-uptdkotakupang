<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\RecordActivity;
use App\Enums\DashboardModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreQueueDisplayRequest;
use App\Http\Resources\QueueDisplayResource;
use App\Models\QueueDisplay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The waiting-room TV displays of Antrean Online (D-051), set up and revoked
 * by administrators. A new display gets a setup link holding its token,
 * shown once.
 */
class QueueDisplayController extends Controller
{
    /**
     * Every display, latest first.
     */
    public function index(): AnonymousResourceCollection
    {
        return QueueDisplayResource::collection(QueueDisplay::query()->with('creator')->latest()->orderByDesc('id')->get());
    }

    /**
     * Set up a display: the link opens /layar on the TV once, which keeps the
     * token. Only the token's hash is kept here.
     */
    public function store(StoreQueueDisplayRequest $request, RecordActivity $recordActivity): JsonResponse
    {
        $token = Str::random(48);
        $display = DB::transaction(function () use ($request, $recordActivity, $token): QueueDisplay {
            $display = QueueDisplay::query()->create([
                'nama' => $request->validated('nama'),
                'token_hash' => QueueDisplay::hash($token),
                'dibuat_oleh' => $request->user()->getKey(),
            ]);
            $recordActivity->handle($request->user(), DashboardModule::Antrean, 'menambahkan', "Layar antrean {$display->nama}");

            return $display;
        });

        return response()->json([
            'layar' => QueueDisplayResource::make($display->load('creator')),
            'tautan' => url('/layar').'#k='.$token,
        ], 201);
    }

    /**
     * Revoke a display: it stops showing the calls at once.
     */
    public function destroy(Request $request, QueueDisplay $queueDisplay, RecordActivity $recordActivity): Response
    {
        DB::transaction(function () use ($request, $queueDisplay, $recordActivity): void {
            $queueDisplay->delete();
            $recordActivity->handle($request->user(), DashboardModule::Antrean, 'mencabut', "Layar antrean {$queueDisplay->nama}");
        });

        return response()->noContent();
    }
}
