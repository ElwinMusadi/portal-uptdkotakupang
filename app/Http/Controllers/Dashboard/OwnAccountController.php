<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateOwnAccountRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The signed-in employee's own profile, in the shape of the dashboard's
 * "akun" settings (D-027). The name and email come from the account, which
 * administrators manage in Akun Pegawai (D-008).
 */
class OwnAccountController extends Controller
{
    /**
     * Show the profile.
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json($this->profile($request->user()));
    }

    /**
     * Change the job title, unit and role in the information workflow. The
     * profile is personal, so it is not in the activity log.
     */
    public function update(UpdateOwnAccountRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->forceFill($request->profile());

        if ($user->isDirty()) {
            $user->save();
        }

        return response()->json($this->profile($user));
    }

    /**
     * @return array{nama: string, jabatan: string, unit: string, peran: string, email: string}
     */
    private function profile(User $user): array
    {
        return [
            'nama' => $user->name,
            'jabatan' => $user->jabatan,
            'unit' => $user->unit,
            'peran' => $user->peran_informasi,
            'email' => $user->email ?? '',
        ];
    }
}
