<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ListPotentialVehiclesRequest;
use App\Http\Resources\PotentialVehicleResource;
use App\Models\PotentialImport;
use App\Models\PotentialVehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

/**
 * The vehicles of Pemetaan PKB with their owners, for collection (D-052,
 * the owner's decision): a search with the largest amounts due first, and
 * one vehicle with every column of the file.
 */
class PotentialVehicleController extends Controller
{
    /**
     * Vehicles listed per search, at most.
     */
    public const int LISTED = 200;

    /**
     * How many vehicles match, and the ones with the most to pay.
     */
    public function index(ListPotentialVehiclesRequest $request): JsonResponse
    {
        $current = PotentialImport::current();

        if ($current === null) {
            return response()->json(['jumlah' => 0, 'kendaraan' => []]);
        }

        $query = PotentialVehicle::query()
            ->whereBelongsTo($current, 'import')
            ->when($request->validated('q'), fn (Builder $query, string $text) => $query->where(fn (Builder $query) => $query
                ->where('nopol', 'like', '%'.str_replace(' ', '', $text).'%')
                ->orWhere('nama', 'like', '%'.$text.'%')))
            ->when($request->kecamatan() !== null, fn (Builder $query) => $query->where('kecamatan', $request->kecamatan()))
            ->when($request->validated('kelurahan'), fn (Builder $query, string $kelurahan) => $query->where('kelurahan', $kelurahan))
            ->when($request->validated('kelompok'), fn (Builder $query, string $group) => $query->where('kelompok', $group))
            ->when($request->validated('roda'), fn (Builder $query, string $wheels) => $query->where('roda', $wheels))
            ->when($request->has('tahun'), fn (Builder $query) => $query->where('tahun_tunggak', (int) $request->validated('tahun')))
            ->when($request->validated('bulan'), fn (Builder $query, int|string $month) => $query->where('bulan_jatuh_tempo', (int) $month));

        return response()->json([
            'jumlah' => (clone $query)->count(),
            'kendaraan' => PotentialVehicleResource::collection($query->orderByDesc('tagihan')->orderBy('urut')->limit(self::LISTED)->get()),
        ]);
    }

    /**
     * A vehicle with every column of the file.
     */
    public function show(PotentialVehicle $potentialVehicle): PotentialVehicleResource
    {
        abort_if($potentialVehicle->potential_import_id !== PotentialImport::current()?->getKey(), 404);

        return PotentialVehicleResource::make($potentialVehicle)->complete();
    }
}
