<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\ReceiveMessage;
use App\Actions\RecordActivity;
use App\Actions\UpdateMessage;
use App\Enums\DashboardModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreMessageRequest;
use App\Http\Requests\Dashboard\UpdateMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Messages from citizens as the dashboard manages them (D-016). Citizens
 * send them through PublicMessageController.
 */
class MessageController extends Controller
{
    /**
     * List every message, newest first, with its responses.
     */
    public function index(): AnonymousResourceCollection
    {
        return MessageResource::collection(
            Message::query()->with('responses.user:id,name')->orderByDesc('diterima')->orderByDesc('id')->get()
        );
    }

    /**
     * Show one message.
     */
    public function show(Message $message): MessageResource
    {
        return new MessageResource($message->load('responses.user:id,name'));
    }

    /**
     * Record a message received through another channel.
     */
    public function store(StoreMessageRequest $request, ReceiveMessage $receiveMessage, RecordActivity $recordActivity): JsonResponse
    {
        $message = DB::transaction(function () use ($request, $receiveMessage, $recordActivity): Message {
            $message = $receiveMessage->handle($request->messageFields(), $request->validated('sumber'));
            $recordActivity->handle($request->user(), DashboardModule::Pesan, 'mencatat', $message->tiket);

            return $message;
        });

        return (new MessageResource($message->load('responses')))->response()->setStatusCode(201);
    }

    /**
     * Change the status and add responses.
     */
    public function update(UpdateMessageRequest $request, Message $message, UpdateMessage $updateMessage): MessageResource
    {
        $message = $updateMessage->handle(
            $request->user(),
            $message,
            $request->validated('status'),
            $request->newResponses($message),
        );

        return new MessageResource($message);
    }
}
