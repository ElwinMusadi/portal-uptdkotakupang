<?php

namespace App\Http\Controllers\Admin;

use App\Actions\IssueActivationLink;
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
     * Create a pending account and return its activation link.
     */
    public function store(StoreAccountRequest $request, IssueActivationLink $issueActivationLink): JsonResponse
    {
        $account = (new User)->forceFill([
            'nip' => $request->validated('nip'),
            'name' => $request->validated('nama'),
            'email' => $request->validated('surel'),
            'role' => $request->enum('peran', UserRole::class),
        ]);
        $link = $issueActivationLink->handle($account);

        Log::info('account.created', [
            'actor' => $request->user()->getKey(),
            'user' => $account->getKey(),
            'role' => $account->role->value,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'akun' => new AccountResource($account),
            'tautan' => $link['url'],
            'kedaluwarsa' => $link['expires_at']->toIso8601String(),
        ], 201);
    }

    /**
     * Change an account's name, email or role.
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
