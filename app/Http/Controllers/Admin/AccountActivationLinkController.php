<?php

namespace App\Http\Controllers\Admin;

use App\Actions\IssueActivationLink;
use App\Http\Controllers\Controller;
use App\Http\Resources\AccountResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AccountActivationLinkController extends Controller
{
    /**
     * Issue a new activation link for an account. For a pending account this
     * replaces the previous link; for any other account it is a password
     * reset: the owner is signed out and chooses a new password through it.
     */
    public function store(Request $request, User $user, IssueActivationLink $issueActivationLink): JsonResponse
    {
        $previous = $user->status;
        $link = $issueActivationLink->handle($user);

        Log::info('account.activation-link', [
            'actor' => $request->user()->getKey(),
            'user' => $user->getKey(),
            'from' => $previous->value,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'akun' => new AccountResource($user),
            'tautan' => $link['url'],
            'kedaluwarsa' => $link['expires_at']->toIso8601String(),
        ], 201);
    }
}
