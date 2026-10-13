<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Cookie-session sign-in for the portal login page and the dashboard
 * (Sanctum SPA authentication; everything is served from one origin).
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * Sign in with NIP and password.
     */
    public function store(LoginRequest $request): JsonResponse
    {
        abort_unless($request->hasSession(), 400, 'Login harus dilakukan dari halaman Login Pegawai.');

        $user = $request->authenticate();

        $request->session()->regenerate();

        return response()->json($this->session($request, $user));
    }

    /**
     * The signed-in employee.
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json($this->session($request, $request->user()));
    }

    /**
     * Sign out and invalidate the session.
     */
    public function destroy(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    /**
     * The signed-in account as the dashboard sees it; modul lists the
     * dashboard modules it may manage (all of them for administrators).
     *
     * @return array{nip: string, nama: string, peran: string, modul: list<string>, masuk: string|null}
     */
    private function session(Request $request, User $user): array
    {
        return [
            'nip' => $user->nip,
            'nama' => $user->name,
            'peran' => $user->role->value,
            'modul' => $user->managedModules(),
            'masuk' => $request->hasSession() ? $request->session()->get('pintu.masuk') : null,
        ];
    }
}
