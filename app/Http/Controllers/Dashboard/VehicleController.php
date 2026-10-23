<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\LookUpVehicle;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class VehicleController extends Controller
{
    /**
     * Look a vehicle up for the Kalkulator PKB by its plate number alone
     * (D-041); any signed-in employee may, as anyone may use the calculator.
     *
     * @throws ValidationException
     */
    public function __invoke(string $nopol, LookUpVehicle $lookUpVehicle): JsonResponse
    {
        if (! LookUpVehicle::isPlateNumber(LookUpVehicle::normalise($nopol))) {
            throw ValidationException::withMessages(['nopol' => 'Tulis nomor polisi seperti DH 1234 AB.']);
        }

        $result = $lookUpVehicle->handle($nopol);

        return match ($result['status']) {
            'found' => response()->json($result['vehicle']),
            'not_found' => response()->json(['message' => 'Data kendaraan tidak ditemukan.'], 404),
            'reference_problem' => response()->json(['message' => "{$result['message']} Isi NJKB secara manual."], 422),
            'outside_region' => response()->json(['message' => "{$result['message']} Lengkapi data kendaraan secara manual."], 422),
            'unavailable' => response()->json(['message' => $result['message']], 503),
        };
    }
}
