<?php

namespace App\Actions;

use App\Enums\DashboardModule;
use App\Models\Message;
use App\Models\MessageResponse;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateMessage
{
    /**
     * Activity log verbs for a status change.
     *
     * @var array<string, string>
     */
    private const array STATUS_VERBS = [
        'baru' => 'menandai baru',
        'diproses' => 'memproses',
        'selesai' => 'menyelesaikan',
    ];

    public function __construct(private RecordActivity $recordActivity) {}

    /**
     * Add the employee's responses and change the status, in one transaction.
     * The employee becomes the message's handler once they work on it, and
     * the change is logged by ticket number only, never by its content.
     *
     * @param  list<array{isi: string, untukPelapor: bool}>  $responses
     */
    public function handle(User $user, Message $message, ?string $status, array $responses): Message
    {
        DB::transaction(function () use ($user, $message, $status, $responses): void {
            foreach ($responses as $response) {
                $message->responses()->save((new MessageResponse)->forceFill([
                    'isi' => $response['isi'],
                    'untuk_pelapor' => $response['untukPelapor'],
                    'user_id' => $user->getKey(),
                ]));
            }

            if ($status !== null) {
                $message->status = $status;
            }

            $statusChanged = $message->isDirty('status');
            $worked = $responses !== [] || ($statusChanged && $message->status !== 'baru');
            if ($worked && $message->ditangani === '') {
                $message->ditangani = $user->name;
            }

            if ($message->isDirty()) {
                $message->save();
            } elseif ($responses !== []) {
                $message->touch();
            }

            if ($responses !== []) {
                $this->recordActivity->handle($user, DashboardModule::Pesan, 'menanggapi', $message->tiket);
            } elseif ($statusChanged) {
                $this->recordActivity->handle($user, DashboardModule::Pesan, self::STATUS_VERBS[$message->status], $message->tiket);
            }
        });

        return $message->load('responses.user');
    }
}
