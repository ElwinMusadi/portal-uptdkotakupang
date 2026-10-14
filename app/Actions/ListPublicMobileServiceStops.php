<?php

namespace App\Actions;

use App\Enums\ServicePointKind;
use App\Exceptions\PublicContentUnavailableException;
use App\Models\MobileServiceStop;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ListPublicMobileServiceStops
{
    /**
     * The time zone the schedule is kept in.
     */
    private const string TIME_ZONE = 'Asia/Makassar';

    /**
     * The order of the kinds on one date and time.
     *
     * @var array<string, int>
     */
    private const array KIND_ORDER = ['keliling' => 0, 'sementara' => 1, 'gerai' => 2];

    /**
     * Build the public schedule of service outside the office (D-043) from
     * the active entries, without internal fields: "jadwal" lists what is
     * served on each date from today (WITA) to the Sunday of next week,
     * "gerai" the fixed outlets that have not closed and "sementara" the
     * temporary counters that have not ended. A source that cannot be read
     * fails the request instead of returning an empty schedule.
     *
     * @return array{dari: string, sampai: string, jadwal: list<array<string, mixed>>, gerai: list<array<string, mixed>>, sementara: list<array<string, mixed>>}
     *
     * @throws PublicContentUnavailableException
     */
    public function handle(): array
    {
        $today = Carbon::now(self::TIME_ZONE)->startOfDay();
        $until = $today->copy()->addWeek()->endOfWeek(Carbon::SUNDAY)->startOfDay();

        try {
            $entries = MobileServiceStop::query()
                ->active()
                ->where(fn ($query) => $query->whereNull('tanggal_selesai')->orWhereDate('tanggal_selesai', '>=', $today->toDateString()))
                ->orderBy('tanggal_mulai')
                ->orderBy('mulai')
                ->orderBy('selesai')
                ->orderBy('id')
                ->get();
        } catch (QueryException $exception) {
            throw new PublicContentUnavailableException('The schedule of service outside the office could not be read.', previous: $exception);
        }

        return [
            'dari' => $today->toDateString(),
            'sampai' => $until->toDateString(),
            'jadwal' => $this->dates($entries, $today, $until),
            'gerai' => $this->ranges($entries, ServicePointKind::Gerai),
            'sementara' => $this->ranges($entries, ServicePointKind::Sementara),
        ];
    }

    /**
     * Every entry on every date it is served between the two days.
     *
     * @param  Collection<int, MobileServiceStop>  $entries
     * @return list<array<string, mixed>>
     */
    private function dates(Collection $entries, Carbon $from, Carbon $until): array
    {
        $served = [];

        for ($date = $from->copy(); $date->lte($until); $date->addDay()) {
            foreach ($entries as $entry) {
                if ($entry->occursOn($date)) {
                    $served[] = [
                        'tanggal' => $date->toDateString(),
                        'jenis' => $entry->jenis->value,
                        'nama' => $entry->name(),
                        ...$this->details($entry),
                    ];
                }
            }
        }

        usort($served, fn (array $a, array $b): int => [$a['tanggal'], $a['mulai'], $a['selesai'], self::KIND_ORDER[$a['jenis']], $a['nama'], $a['lokasi']]
            <=> [$b['tanggal'], $b['mulai'], $b['selesai'], self::KIND_ORDER[$b['jenis']], $b['nama'], $b['lokasi']]);

        return $served;
    }

    /**
     * The entries of one kind with their weekdays and dates.
     *
     * @param  Collection<int, MobileServiceStop>  $entries
     * @return list<array<string, mixed>>
     */
    private function ranges(Collection $entries, ServicePointKind $kind): array
    {
        return $entries
            ->filter(fn (MobileServiceStop $entry): bool => $entry->jenis === $kind)
            ->sortBy([
                fn (MobileServiceStop $a, MobileServiceStop $b): int => $a->tanggal_mulai?->toDateString() <=> $b->tanggal_mulai?->toDateString(),
                fn (MobileServiceStop $a, MobileServiceStop $b): int => $a->nama <=> $b->nama,
                fn (MobileServiceStop $a, MobileServiceStop $b): int => ($a->hari[0] ?? 0) <=> ($b->hari[0] ?? 0),
                fn (MobileServiceStop $a, MobileServiceStop $b): int => $a->mulai <=> $b->mulai,
            ])
            ->map(fn (MobileServiceStop $entry): array => [
                'nama' => $entry->nama,
                'tanggalMulai' => $entry->tanggal_mulai?->toDateString(),
                'tanggalSelesai' => $entry->tanggal_selesai?->toDateString(),
                'hari' => $entry->hari,
                ...$this->details($entry),
            ])
            ->values()
            ->all();
    }

    /**
     * The fields every public entry carries.
     *
     * @return array{mulai: string, selesai: string, lokasi: string, kecamatan: string, layanan: string, catatan: string}
     */
    private function details(MobileServiceStop $entry): array
    {
        return [
            'mulai' => $entry->mulai,
            'selesai' => $entry->selesai,
            'lokasi' => $entry->lokasi,
            'kecamatan' => $entry->kecamatan,
            'layanan' => $entry->layanan,
            'catatan' => $entry->catatan,
        ];
    }
}
