<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\DeleteEntries;
use App\Enums\DashboardModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\DeleteMessagesRequest;
use App\Models\Message;
use Illuminate\Http\Response;

class MessageDeletionController extends Controller
{
    /**
     * Delete the listed messages and their responses; only administrators
     * may (MessagePolicy). The log names the ticket, not the content.
     */
    public function store(DeleteMessagesRequest $request, DeleteEntries $deleteEntries): Response
    {
        $deleteEntries->handle($request->user(), DashboardModule::Pesan, Message::class, $request->ids(), fn (Message $message): string => $message->tiket);

        return response()->noContent();
    }
}
