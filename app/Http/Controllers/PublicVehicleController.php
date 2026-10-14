<?php

namespace App\Http\Controllers;

use App\Actions\LookUpVehicle;
use App\Http\Requests\CheckPublicVehicleRequest;
use Illuminate\Http\JsonResponse;

class PublicVehicleController extends Controller
{
    /**
     * Give a visitor the data the portal's tax check needs (D-041), once the
     * last five characters of the vehicle's chassis number match. A wrong
     * plate, wrong chassis characters and an unknown vehicle get the same
     * answer, so the check reveals nothing about plates it does not confirm.
     * The chassis number itself is never sent.
     */
    public function __invoke(CheckPublicVehicleRequest $request, LookUpVehicle $lookUpVehicle): JsonResponse
    {
        $result = $lookUpVehicle->handle($request->validated('nopol'));

        if ($result['status'] === 'unavailable') {
            return response()->json(['message' => $result['message']], 503);
        }

        $chassis = LookUpVehicle::normalise($result['chassis'] ?? '');
        $confirmed = strlen($chassis) >= 5 && hash_equals(substr($chassis, -5), $request->validated('rangka'));

        if (! $confirmed) {
            return response()->json(['message' => 'Nomor polisi atau nomor rangka tidak cocok dengan data kendaraan.'], 404);
        }

        if ($result['status'] === 'reference_problem') {
            return response()->json(['message' => "{$result['message']} Silakan cek biaya pajak di loket Samsat."], 422);
        }

        return response()->json($result['vehicle']);
    }
}
