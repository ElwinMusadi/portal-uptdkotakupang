<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ActivateAccountRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Lets a pending account choose its password through a signed, expiring,
 * single-use link (see `php artisan pintu:create-admin`).
 */
class AccountActivationController extends Controller
{
    /**
     * Show the form to choose a password.
     */
    public function show(Request $request, User $user): Response
    {
        if (! $this->linkIsValid($request, $user)) {
            return response()->view('auth.aktivasi', ['user' => null], 410);
        }

        return response()->view('auth.aktivasi', ['user' => $user]);
    }

    /**
     * Save the password and activate the account.
     */
    public function store(ActivateAccountRequest $request, User $user): RedirectResponse|Response
    {
        if (! $this->linkIsValid($request, $user)) {
            return response()->view('auth.aktivasi', ['user' => null], 410);
        }

        $user->forceFill([
            'password' => $request->validated('sandi'),
            'status' => AccountStatus::Active,
        ])->save();

        Log::info('auth.activated', ['user' => $user->getKey(), 'ip' => $request->ip()]);

        return redirect('/login?aktif=1');
    }

    /**
     * Signed and not expired, still pending, and no password set through it yet.
     */
    private function linkIsValid(Request $request, User $user): bool
    {
        return $request->hasValidSignature()
            && ($user->getAttributes()['status'] ?? null) === AccountStatus::Pending->value
            && hash_equals($user->activationKey(), (string) $request->query('kunci'));
    }
}
