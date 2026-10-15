<?php

namespace App\Actions;

use App\Enums\QueueStage;
use App\Enums\QueueTicketStatus;
use App\Models\QueueTicket;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * A queue ticket on the taxpayer's tracking page, opened from the ticket's QR
 * (D-051): the number, service, plate number as written on the ticket, where
 * the documents are and what to do, and the stages before and after. It
 * names no employee.
 */
class ShowPublicQueueTicket
{
    /**
     * @return array{nomor: string, tanggal: string, layanan: array{kode: string, nama: string}, nopol: ?string, status: string, tahap: ?string, petunjuk: string, tahapan: list<array{kunci: string, nama: string, keadaan: string}>, dicetak: ?string}
     *
     * @throws ModelNotFoundException<QueueTicket>
     */
    public function handle(string $code): array
    {
        $ticket = QueueTicket::query()->with('service')->where('kode_lacak', $code)->firstOrFail();
        $cancelled = $ticket->status === QueueTicketStatus::Batal;
        $stages = $ticket->stages();
        $current = $ticket->tahap === null ? count($stages) : array_search($ticket->tahap, $stages, true);

        return [
            'nomor' => $ticket->nomor,
            'tanggal' => $ticket->tanggal,
            'layanan' => ['kode' => $ticket->service->kode, 'nama' => $ticket->service->nama],
            'nopol' => $ticket->plate(),
            'status' => $ticket->status->value,
            'tahap' => $ticket->tahap?->value,
            'petunjuk' => $cancelled ? 'Nomor antrean ini dibatalkan petugas. Silakan tanyakan ke Meja Informasi.' : ($ticket->tahap?->instruction() ?? ''),
            'tahapan' => array_map(fn (QueueStage $stage, int $index): array => [
                'kunci' => $stage->value,
                'nama' => $stage->label(),
                'keadaan' => match (true) {
                    $index < $current => 'selesai',
                    $index === $current && ! $cancelled => 'sekarang',
                    default => 'nanti',
                },
            ], $stages, array_keys($stages)),
            'dicetak' => $ticket->created_at?->toIso8601String(),
        ];
    }
}
