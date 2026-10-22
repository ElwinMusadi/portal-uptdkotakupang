<?php

namespace App\Http\Controllers\Admin;

use App\Actions\AmbilPegawaiSikap;
use App\Actions\CariPegawaiSikap;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SearchSikapEmployeesRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * SIKAP's employees for the "Tambah akun" form of Akun Pegawai (D-075). The
 * browser asks PINTU and PINTU asks SIKAP with its own key, which never
 * reaches the browser. Each employee says whether their NIP already has an
 * account, as an account cannot be made twice.
 */
class SikapEmployeeController extends Controller
{
    /**
     * Search active employees by part of a name or NIP.
     */
    public function index(SearchSikapEmployeesRequest $request, CariPegawaiSikap $cariPegawaiSikap): JsonResponse
    {
        $result = $cariPegawaiSikap->handle($request->validated('q'));

        if ($result['status'] === 'unavailable') {
            return response()->json(['message' => $result['message']], 503);
        }

        $registered = User::query()->whereIn('nip', array_column($result['pegawai'], 'nip'))->pluck('nip')->all();

        return response()->json([
            'pegawai' => array_map(
                fn (array $employee): array => $employee + ['sudahPunyaAkun' => in_array($employee['nip'], $registered, true)],
                $result['pegawai'],
            ),
            'total' => $result['total'],
        ]);
    }

    /**
     * Read the employee an administrator picked. SIKAP logs every such read.
     */
    public function show(string $nip, AmbilPegawaiSikap $ambilPegawaiSikap): JsonResponse
    {
        $result = $ambilPegawaiSikap->handle($nip);

        return match ($result['status']) {
            'found' => response()->json($result['pegawai'] + ['sudahPunyaAkun' => User::query()->where('nip', $nip)->exists()]),
            'not_found' => response()->json(['message' => 'Pegawai tidak ditemukan atau tidak aktif di SIKAP.'], 404),
            'unavailable' => response()->json(['message' => $result['message']], 503),
        };
    }
}
