<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAccountStatusRequest;
use App\Http\Resources\AccountResource;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AccountStatusController extends Controller
{
    /**
     * Suspend, reactivate or deactivate an account. An account that stops
     * being active loses its remembered devices at once, and its open
     * sessions end on their next request (EnsureAccountIsActive).
     */
    public function update(UpdateAccountStatusRequest $request, User $user): AccountResource
    {
        $previous = $user->status;
        $status = $request->newStatus();

        $user->forceFill(['status' => $status]);

        if ($status !== AccountStatus::Active) {
            $user->setRememberToken(Str::random(60));
        }

        $user->save();

        Log::info('account.status', [
            'actor' => $request->user()->getKey(),
            'user' => $user->getKey(),
            'from' => $previous->value,
            'to' => $status->value,
            'ip' => $request->ip(),
        ]);

        return new AccountResource($user);
    }
}
