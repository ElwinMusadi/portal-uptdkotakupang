<?php

namespace App\Actions;

use App\Enums\AccountStatus;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class IssueActivationLink
{
    /**
     * Minutes an activation link stays valid.
     */
    public const int MINUTES = 60;

    /**
     * Put the account into the pending state, saving it if it is new, and
     * build the signed link with which its owner chooses a password.
     *
     * The account gets a new unusable password and remember token, so its
     * sessions and remembered devices stop working and so do earlier links.
     * Each link carries a key derived from the password hash, which also
     * makes it single use: activation changes the password again.
     *
     * @return array{url: string, expires_at: CarbonInterface}
     */
    public function handle(User $user): array
    {
        $user->forceFill([
            'password' => Str::password(64),
            'status' => AccountStatus::Pending,
        ]);
        $user->setRememberToken(Str::random(60));
        $user->save();

        $expiresAt = now()->addMinutes(self::MINUTES);

        return [
            'url' => URL::temporarySignedRoute('aktivasi.show', $expiresAt, [
                'user' => $user,
                'kunci' => $user->activationKey(),
            ]),
            'expires_at' => $expiresAt,
        ];
    }
}
