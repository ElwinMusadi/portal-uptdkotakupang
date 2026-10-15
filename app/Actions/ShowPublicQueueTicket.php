<?php

namespace App\Actions;

use App\Enums\QueueStage;
use App\Enums\QueueTicketStatus;
use App\Models\QueueEvent;
use App\Models\QueueTicket;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * A queue ticket on the taxpayer's tracking page, opened from the ticket's QR
 * (D-051): the number, service, plate number as written on the ticket, where
 * the documents are and what to do, how many numbers wait ahead at a
 * counter, and the stages before and after with the time each was done. It
 * names no employee.
 */
class ShowPublicQueueTicket
{
    /**
     * @return array{nomor: string, tanggal: string, layanan: array{kode: string, nama: string}, nopol: ?string, status: string, tahap: ?string, petunjuk: string, posisi: ?int, tahapan: list<array{kunci: string, nama: string, keadaan: string, selesai: ?string}>, dicetak: ?string}
     *
     * @throws ModelNotFoundException<QueueTicket>
     */
    public function handle(string $code): array
    {
        $ticket = QueueTicket::query()->with(['service', 'counter'])->where('kode_lacak', $code)->firstOrFail();
        $cancelled = $ticket->status === QueueTicketStatus::Batal;
        $stages = $ticket->stages();
        $current = $ticket->tahap === null ? count($stages) : array_search($ticket->tahap, $stages, true);
        $finished = $ticket->events()->where('aksi', 'tahap selesai')->get()->mapWithKeys(fn (QueueEvent $event): array => [$event->tahap->value => $event->created_at]);

        return [
            'nomor' => $ticket->nomor,
            'tanggal' => $ticket->tanggal,
            'layanan' => ['kode' => $ticket->service->kode, 'nama' => $ticket->service->nama],
            'nopol' => $ticket->plate(),
            'status' => $ticket->status->value,
            'tahap' => $ticket->tahap?->value,
            'petunjuk' => $this->instruction($ticket),
            'posisi' => $this->ahead($ticket),
            'tahapan' => array_map(fn (QueueStage $stage, int $index): array => [
                'kunci' => $stage->value,
                'nama' => $stage->label(),
                'keadaan' => match (true) {
                    $index < $current => 'selesai',
                    $index === $current && ! $cancelled => 'sekarang',
                    default => 'nanti',
                },
                'selesai' => $index < $current ? $finished->get($stage->value)?->toIso8601String() : null,
            ], $stages, array_keys($stages)),
            'dicetak' => $ticket->created_at?->toIso8601String(),
        ];
    }

    /**
     * What the taxpayer should do now.
     */
    private function instruction(QueueTicket $ticket): string
    {
        $stage = $ticket->tahap?->label();

        return match ($ticket->status) {
            QueueTicketStatus::Batal => 'Nomor antrean ini dibatalkan petugas. Silakan tanyakan ke Meja Informasi.',
            QueueTicketStatus::Selesai => 'Berkas Anda sudah selesai diproses. Terima kasih.',
            QueueTicketStatus::Dipanggil => 'Nomor Anda dipanggil. Silakan menuju '.($ticket->counter->nama ?? $stage).'.',
            QueueTicketStatus::Ditunda => "Nomor Anda sudah dipanggil ke {$stage}, tetapi Anda belum datang. Silakan lapor ke petugas {$stage}.",
            QueueTicketStatus::PerubahanData => 'Data kendaraan Anda sedang diubah petugas. Setelah itu berkas Anda kembali ditetapkan.',
            QueueTicketStatus::Menunggu => $ticket->tahap?->instruction() ?? '',
        };
    }

    /**
     * How many numbers wait ahead at a counter that calls them, all services
     * together; null when the ticket does not wait to be called.
     */
    private function ahead(QueueTicket $ticket): ?int
    {
        if ($ticket->status !== QueueTicketStatus::Menunggu || ! $ticket->tahap?->isCalled()) {
            return null;
        }

        return QueueTicket::query()
            ->where('tahap', $ticket->tahap)
            ->where('status', QueueTicketStatus::Menunggu)
            ->where(fn ($query) => $query
                ->where('masuk_tahap_pada', '<', $ticket->masuk_tahap_pada)
                ->orWhere(fn ($query) => $query->where('masuk_tahap_pada', $ticket->masuk_tahap_pada)->where('id', '<', $ticket->id)))
            ->count();
    }
}
