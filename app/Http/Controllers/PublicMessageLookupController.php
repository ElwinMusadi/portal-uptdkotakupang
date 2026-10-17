<?php

namespace App\Http\Controllers;

use App\Http\Requests\LookUpPublicMessageRequest;
use App\Models\Message;
use Illuminate\Http\JsonResponse;

class PublicMessageLookupController extends Controller
{
    /**
     * The tracking code of a message, for its sender who kept the ticket
     * number but not the tracking page (D-058). The contact address the
     * message was sent with must match too; any mismatch gets the same
     * answer, so a ticket number alone reveals nothing.
     */
    public function __invoke(LookUpPublicMessageRequest $request): JsonResponse
    {
        $message = Message::query()->where('tiket', $request->ticket())->first();

        if ($message === null || ! $message->hasContact($request->validated('kontak'))) {
            return response()->json(['message' => 'Nomor tiket dan kontak tidak cocok dengan pesan mana pun. Periksa kembali keduanya.'], 404);
        }

        return response()->json(['kode' => $message->kode_lacak]);
    }
}
