<?php

namespace App\Actions;

use App\Enums\QueueStage;
use App\Enums\QueueTicketStatus;
use App\Models\QueueCounter;
use App\Models\QueueEvent;
use App\Models\QueueTicket;
use Illuminate\Support\Carbon;

/**
 * What a waiting-room TV display shows (Antrean Online slice 2, D-051): the
 * latest calls of the day, newest first, which it announces aloud as they
 * come; the number each active counter calls; and how many numbers wait at
 * each calling stage. Plate numbers are shown as written (the owner's
 * decision), so only a registered display reads this.
 */
class ShowQueueDisplay
{
    /**
     * Calls listed, newest first.
     */
    private const int CALLS = 8;

    /**
     * @return array{waktu: string, panggilan: list<array<string, mixed>>, loket: list<array<string, mixed>>, menunggu: list<array{tahap: string, nama: string, jumlah: int}>}
     */
    public function handle(): array
    {
        $today = now(QueueTicket::TIME_ZONE)->startOfDay();

        $calls = QueueEvent::query()
            ->with(['ticket.service', 'counter'])
            ->whereIn('aksi', ['dipanggil', 'dipanggil ulang'])
            ->where('created_at', '>=', $today->copy()->utc())
            ->latest('id')
            ->limit(self::CALLS)
            ->get();

        $called = QueueTicket::query()
            ->with('service')
            ->where('status', QueueTicketStatus::Dipanggil)
            ->whereNotNull('queue_counter_id')
            ->get()
            ->keyBy('queue_counter_id');

        $waiting = QueueTicket::query()
            ->where('status', QueueTicketStatus::Menunggu)
            ->selectRaw('tahap, COUNT(*) AS jumlah')
            ->groupBy('tahap')
            ->pluck('jumlah', 'tahap');

        return [
            'waktu' => now()->toIso8601String(),
            'panggilan' => $calls->map(fn (QueueEvent $event): array => [
                'id' => $event->id,
                ...$this->ticket($event->ticket, $today),
                'loket' => $event->counter === null ? null : ['nama' => $event->counter->nama, 'suara' => $event->counter->nama_suara],
                'waktu' => $event->created_at?->toIso8601String(),
            ])->all(),
            'loket' => QueueCounter::query()->where('aktif', true)->ordered()->get()->map(fn (QueueCounter $counter): array => [
                'nama' => $counter->nama,
                'tahap' => $counter->tahap->value,
                'dipanggil' => $called->has($counter->id) ? $this->ticket($called[$counter->id], $today) : null,
            ])->all(),
            'menunggu' => array_values(array_map(fn (QueueStage $stage): array => [
                'tahap' => $stage->value,
                'nama' => $stage->label(),
                'jumlah' => (int) ($waiting[$stage->value] ?? 0),
            ], array_filter(QueueStage::cases(), fn (QueueStage $stage): bool => $stage->isCalled()))),
        ];
    }

    /**
     * A ticket as the display shows it; tickets of an earlier day carry
     * their date.
     *
     * @return array{nomor: string, tanggal: string, hariIni: bool, nopol: ?string, layanan: string}
     */
    private function ticket(QueueTicket $ticket, Carbon $today): array
    {
        return [
            'nomor' => $ticket->nomor,
            'tanggal' => $ticket->tanggal,
            'hariIni' => $ticket->tanggal === $today->toDateString(),
            'nopol' => $ticket->plate(),
            'layanan' => $ticket->service->nama,
        ];
    }
}
