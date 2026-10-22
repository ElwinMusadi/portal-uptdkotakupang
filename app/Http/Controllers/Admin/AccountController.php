<?php

namespace App\Http\Controllers\Admin;

use App\Actions\AmbilPegawaiSikap;
use App\Actions\IssueActivationLink;
use App\Enums\DashboardModule;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAccountRequest;
use App\Http\Requests\Admin\UpdateAccountRequest;
use App\Http\Resources\AccountResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Accounts that administrators manage from the dashboard. A new account
 * starts pending and its owner chooses a password through the activation
 * link returned once here, so administrators never know anyone's password.
 */
class AccountController extends Controller
{
    /**
     * List every account by name.
     */
    public function index(): AnonymousResourceCollection
    {
        return AccountResource::collection(User::query()->orderBy('name')->orderBy('nip')->get());
    }

    /**
     * Create a pending account and return its activation link. For an
     * employee picked from SIKAP the server reads them again by NIP and uses
     * SIKAP's name, not the one the browser sent (D-075).
     *
     * @throws ValidationException
     */
    public function store(StoreAccountRequest $request, IssueActivationLink $issueActivationLink, AmbilPegawaiSikap $ambilPegawaiSikap): JsonResponse
    {
        $name = $request->validated('nama');

        if ($request->fromSikap()) {
            $employee = $ambilPegawaiSikap->handle($request->validated('nip'));

            if ($employee['status'] === 'not_found') {
                throw ValidationException::withMessages(['nip' => 'Pegawai dengan NIP ini tidak ditemukan atau tidak aktif di SIKAP.']);
            }

            if ($employee['status'] === 'unavailable') {
                return response()->json(['message' => $employee['message']], 503);
            }

            $name = $employee['pegawai']['nama'];
        }

        $account = (new User)->forceFill([
            'nip' => $request->validated('nip'),
            'name' => $name,
            'email' => $request->validated('surel'),
            'role' => $request->enum('peran', UserRole::class),
            'modules' => DashboardModule::ordered($request->validated('modul', [])),
        ]);
        $link = $issueActivationLink->handle($account);

        Log::info('account.created', [
            'actor' => $request->user()->getKey(),
            'user' => $account->getKey(),
            'role' => $account->role->value,
            'modules' => $account->assignedModules(),
            'source' => $request->fromSikap() ? 'sikap' : 'manual',
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'akun' => new AccountResource($account),
            'tautan' => $link['url'],
            'kedaluwarsa' => $link['expires_at']->toIso8601String(),
        ], 201);
    }

    /**
     * Change an account's name, email, role or assigned modules.
     */
    public function update(UpdateAccountRequest $request, User $user): AccountResource
    {
        $user->forceFill($request->accountAttributes())->save();

        if ($user->wasChanged()) {
            Log::info('account.updated', [
                'actor' => $request->user()->getKey(),
                'user' => $user->getKey(),
                'fields' => array_keys(Arr::except($user->getChanges(), ['updated_at'])),
                'ip' => $request->ip(),
            ]);
        }

        return new AccountResource($user);
    }
}
