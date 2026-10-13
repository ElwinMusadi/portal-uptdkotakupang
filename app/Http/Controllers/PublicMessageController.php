<?php

namespace App\Http\Controllers;

use App\Actions\ReceiveMessage;
use App\Http\Requests\SubmitPublicMessageRequest;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PublicMessageController extends Controller
{
    /**
     * Receive a message from the portal's contact form and return its
     * ticket number (D-016). A message that fills the hidden field is
     * answered the same way but not stored, so bots learn nothing. The log
     * records the ticket only, never the sender or the content.
     */
    public function __invoke(SubmitPublicMessageRequest $request, ReceiveMessage $receiveMessage): JsonResponse
    {
        if ($request->isFromBot()) {
            return response()->json(['tiket' => Message::newTicket()], 201);
        }

        $message = $receiveMessage->handle($request->messageFields(), 'formulir');
        Log::info('message.received', ['tiket' => $message->tiket]);

        return response()->json(['tiket' => $message->tiket], 201);
    }
}
