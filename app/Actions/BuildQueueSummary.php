<?php

namespace App\Actions;

use App\Enums\QueueStage;
use App\Enums\QueueTicketStatus;
use App\Models\QueueEvent;
use App\Models\QueueService;
use App\Models\QueueTicket;
use Carbon\CarbonPeriod;

/**
 * The queue summary of a day or a range of days (D-051 slice 4): numbers
 * printed, finished, cancelled and still running, per service and per day,
 * and per stage how many were finished there or passed on their own, how
 * many were put aside, and the median wait for a call and time spent at the
 * stage. Tickets count on the day they were printed, also for what happened
 * to them later.
 *
 * A time is measured only from a moment someone recorded: the print, or a
 * stage finished by a counter or station. When a stage was passed on its
 * own, the next stage's start is unknown, so it is left out. The median
 * keeps documents that stayed overnight from swamping the figures.
 */
class BuildQueueSummary
{
    /**
     * @return array{dari: string, sampai: string, jumlah: array{dicetak: int, selesai: int, batal: int, berjalan: int, ditunda: int}, layanan: list<array{kode: string, nama: string, dicetak: int, selesai: int, batal: int, berjalan: int}>, harian: list<array{tanggal: string, dicetak: int, selesai: int, batal: int, berjalan: int}>, tahap: list<array{kunci: string, nama: string, dipanggil: bool, selesai: int, otomatis: int, ditunda: int, tunggu: ?int, lama: ?int}>}
     */
    public function handle(string $from, string $until): array
    {
        $printed = fn () => QueueTicket::query()->whereBetween('tanggal', [$from, $until]);
        $tickets = $printed()->get(['id', 'queue_service_id', 'tanggal', 'status', 'tahap', 'tahap_layanan', 'created_at'])->keyBy('id');
        $stages = array_fill_keys(array_map(fn (QueueStage $stage): string => $stage->value, QueueStage::cases()), [
            'selesai' => 0, 'otomatis' => 0, 'ditunda' => 0, 'tunggu' => [], 'lama' => [],
        ]);
        $putAside = 0;

        $timeline = [];
        $events = QueueEvent::query()
            ->whereIn('queue_ticket_id', $printed()->select('id'))
            ->orderBy('queue_ticket_id')
            ->orderBy('id')
            ->cursor();
        foreach ($events as $event) {
            if ($timeline !== [] && $timeline[0]->queue_ticket_id !== $event->queue_ticket_id) {
                $putAside += $this->measure($tickets[$timeline[0]->queue_ticket_id], $timeline, $stages);
                $timeline = [];
            }
            $timeline[] = $event;
        }
        if ($timeline !== []) {
            $putAside += $this->measure($tickets[$timeline[0]->queue_ticket_id], $timeline, $stages);
        }

        $byService = $tickets->groupBy('queue_service_id');
        $byDay = $tickets->groupBy('tanggal');

        return [
            'dari' => $from,
            'sampai' => $until,
            'jumlah' => [...$this->count($tickets->all()), 'ditunda' => $putAside],
            'layanan' => QueueService::query()->ordered()->get()->map(fn (QueueService $service): array => [
                'kode' => $service->kode,
                'nama' => $service->nama,
                ...$this->count($byService->get($service->getKey(), collect())->all()),
            ])->all(),
            'harian' => array_map(fn (string $day): array => [
                'tanggal' => $day,
                ...$this->count($byDay->get($day, collect())->all()),
            ], array_map(fn ($day): string => $day->toDateString(), CarbonPeriod::create($from, $until)->toArray())),
            'tahap' => array_map(fn (QueueStage $stage): array => [
                'kunci' => $stage->value,
                'nama' => $stage->label(),
                'dipanggil' => $stage->isCalled(),
                'selesai' => $stages[$stage->value]['selesai'],
                'otomatis' => $stages[$stage->value]['otomatis'],
                'ditunda' => $stages[$stage->value]['ditunda'],
                'tunggu' => $this->median($stages[$stage->value]['tunggu']),
                'lama' => $this->median($stages[$stage->value]['lama']),
            ], QueueStage::cases()),
        ];
    }

    /**
     * Follow one ticket's history and add what it says to the stage figures.
     * Returns 1 when someone put the ticket aside, 0 otherwise.
     *
     * @param  list<QueueEvent>  $events  oldest first
     * @param  array<string, array{selesai: int, otomatis: int, ditunda: int, tunggu: list<int>, lama: list<int>}>  $figures
     */
    private function measure(QueueTicket $ticket, array $events, array &$figures): int
    {
        $stages = array_map(fn (QueueStage $stage): string => $stage->value, $ticket->stages());
        $position = array_flip($stages);
        $current = $ticket->tahap === null ? count($stages) : $position[$ticket->tahap->value];
        // Per stage: when it started and whether someone recorded that moment
        $started = $stages === [] ? [] : [$stages[0] => [$ticket->created_at->getTimestamp(), true]];
        $finished = [];
        $called = [];
        $aside = [];

        foreach ($events as $event) {
            $stage = $event->tahap?->value;
            $at = $event->created_at->getTimestamp();

            if ($event->aksi === 'tahap selesai') {
                $finished[$stage] = [$at, $event->otomatis];
                $next = $stages[$position[$stage] + 1] ?? null;
                if ($next !== null) {
                    $started[$next] = [$at, ! $event->otomatis];
                }
            } elseif ($event->aksi === 'dipanggil') {
                $called[$stage] ??= $at;
            } elseif ($event->aksi === 'ditunda' && ! $event->otomatis) {
                $aside[$stage] = true;
            }
        }

        foreach ($stages as $index => $stage) {
            [$startedAt, $recorded] = $started[$stage] ?? [null, false];

            if ($index < $current && isset($finished[$stage])) {
                [$finishedAt, $automatic] = $finished[$stage];
                $figures[$stage][$automatic ? 'otomatis' : 'selesai']++;
                if (! $automatic && $recorded) {
                    $figures[$stage]['lama'][] = max(0, $finishedAt - $startedAt);
                }
            }
            if ($index <= $current && $recorded && isset($called[$stage])) {
                $figures[$stage]['tunggu'][] = max(0, $called[$stage] - $startedAt);
            }
            if (isset($aside[$stage])) {
                $figures[$stage]['ditunda']++;
            }
        }

        return $aside === [] ? 0 : 1;
    }

    /**
     * @param  list<QueueTicket>  $tickets
     * @return array{dicetak: int, selesai: int, batal: int, berjalan: int}
     */
    private function count(array $tickets): array
    {
        $finished = count(array_filter($tickets, fn (QueueTicket $ticket): bool => $ticket->status === QueueTicketStatus::Selesai));
        $cancelled = count(array_filter($tickets, fn (QueueTicket $ticket): bool => $ticket->status === QueueTicketStatus::Batal));

        return ['dicetak' => count($tickets), 'selesai' => $finished, 'batal' => $cancelled, 'berjalan' => count($tickets) - $finished - $cancelled];
    }

    /**
     * The middle value in seconds, or null without any.
     *
     * @param  list<int>  $seconds
     */
    private function median(array $seconds): ?int
    {
        if ($seconds === []) {
            return null;
        }

        sort($seconds);
        $middle = intdiv(count($seconds), 2);

        return count($seconds) % 2 === 1 ? $seconds[$middle] : intdiv($seconds[$middle - 1] + $seconds[$middle], 2);
    }
}
