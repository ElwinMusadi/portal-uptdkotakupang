<?php

namespace App\Actions;

use App\Models\Message;
use Illuminate\Support\Facades\DB;

class ReceiveMessage
{
    /**
     * Store a new message under a fresh ticket number and tracking code, as
     * "baru" and received now. The sender's fields are kept exactly as given.
     *
     * @param  array{jenis: string, nama: string, kontak: string, layanan: string, isi: string}  $fields
     */
    public function handle(array $fields, string $source): Message
    {
        return DB::transaction(function () use ($fields, $source): Message {
            $message = new Message;
            $message->forceFill([
                ...$fields,
                'tiket' => Message::newTicket(),
                'kode_lacak' => Message::newTrackingCode(),
                'sumber' => $source,
                'status' => 'baru',
                'diterima' => now(),
                'ditangani' => '',
                'urutan' => (int) (Message::query()->max('urutan') ?? -1) + 1,
            ])->save();

            return $message;
        });
    }
}
