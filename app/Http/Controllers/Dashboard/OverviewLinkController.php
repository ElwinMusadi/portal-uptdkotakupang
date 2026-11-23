<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\RecordActivity;
use App\Enums\DashboardModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreOverviewLinkRequest;
use App\Http\Resources\OverviewLinkResource;
use App\Models\OverviewLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The links to the Ikhtisar page (D-099), made and revoked by
 * administrators in Pengaturan › Tautan Ikhtisar. A new link is shown once.
 * Both are logged under Laporan Penerimaan, whose figures the page shows.
 */
class OverviewLinkController extends Controller
{
    /**
     * Every link, latest first.
     */
    public function index(): AnonymousResourceCollection
    {
        return OverviewLinkResource::collection(OverviewLink::query()->with('creator')->latest()->orderByDesc('id')->get());
    }

    /**
     * Make a link: /ikhtisar with the token in its fragment, which browsers
     * never send to the server. Only the token's hash is kept.
     */
    public function store(StoreOverviewLinkRequest $request, RecordActivity $recordActivity): JsonResponse
    {
        $token = Str::random(48);
        $link = DB::transaction(function () use ($request, $recordActivity, $token): OverviewLink {
            $link = OverviewLink::query()->create([
                'nama' => $request->validated('nama'),
                'token_hash' => OverviewLink::hash($token),
                'dibuat_oleh' => $request->user()->getKey(),
            ]);
            $recordActivity->handle($request->user(), DashboardModule::Penerimaan, 'menambahkan', "Tautan ikhtisar {$link->nama}");

            return $link;
        });

        return response()->json([
            'tautan' => OverviewLinkResource::make($link->load('creator')),
            'alamat' => url('/ikhtisar').'#k='.$token,
        ], 201);
    }

    /**
     * Revoke a link: whoever holds it can no longer open the page, and a page
     * already open stops updating.
     */
    public function destroy(Request $request, OverviewLink $overviewLink, RecordActivity $recordActivity): Response
    {
        DB::transaction(function () use ($request, $overviewLink, $recordActivity): void {
            $overviewLink->delete();
            $recordActivity->handle($request->user(), DashboardModule::Penerimaan, 'mencabut', "Tautan ikhtisar {$overviewLink->nama}");
        });

        return response()->noContent();
    }
}
