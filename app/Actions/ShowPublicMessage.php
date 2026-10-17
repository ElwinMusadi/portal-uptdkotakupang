<?php

namespace App\Actions;

use App\Models\Message;
use App\Models\MessageResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * A message on its sender's tracking page (D-058): the ticket, what it is
 * about, the sender's own text, its status, and the replies staff wrote for
 * the sender, without the employees' names. Internal notes and the sender's
 * name and contact address stay in the dashboard.
 */
class ShowPublicMessage
{
    /**
     * @return array{tiket: string, jenis: string, layanan: string, isi: string, diterima: string, status: string, balasan: list<array{isi: string, waktu: string}>}
     *
     * @throws ModelNotFoundException<Message>
     */
    public function handle(string $code): array
    {
        $message = Message::query()->where('kode_lacak', $code)->firstOrFail();

        return [
            'tiket' => $message->tiket,
            'jenis' => $message->jenis,
            'layanan' => $message->layanan,
            'isi' => $message->isi,
            'diterima' => $message->diterima->toIso8601String(),
            'status' => $message->status,
            'balasan' => $message->responses()->where('untuk_pelapor', true)->get()
                ->map(fn (MessageResponse $response): array => [
                    'isi' => $response->isi,
                    'waktu' => $response->created_at->toIso8601String(),
                ])->values()->all(),
        ];
    }
}
