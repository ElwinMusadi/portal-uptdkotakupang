<?php

namespace App\Actions;

use App\Enums\ServicePointKind;
use App\Models\MobileServiceStop;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * The counters an STS file can be imported for (D-047): the office counter,
 * the three Samsat Keliling units, and every fixed outlet and temporary
 * counter in Layanan Luar Kantor (D-043). An outlet whose hours differ per
 * day has several schedule rows but is one counter, keyed by its name.
 */
class ListRevenueCounters
{
    /**
     * The office counter's key.
     */
    public const string OFFICE = 'kantor';

    /**
     * Every counter, the fixed ones first and the outlets and temporary
     * counters by name.
     *
     * @return list<array{kunci: string, nama: string, jenis: string}>
     */
    public function handle(): array
    {
        $places = MobileServiceStop::query()
            ->whereIn('jenis', [ServicePointKind::Gerai, ServicePointKind::Sementara])
            ->get()
            ->map(fn (MobileServiceStop $stop): array => $this->place($stop))
            ->unique('kunci')
            ->sortBy(fn (array $counter): string => ($counter['jenis'] === 'gerai' ? '0' : '1').mb_strtolower($counter['nama']))
            ->values()
            ->all();

        return [...$this->fixed(), ...$places];
    }

    /**
     * The counter with this key, or null.
     *
     * @return ?array{kunci: string, nama: string, jenis: string}
     */
    public function find(string $key): ?array
    {
        return collect($this->handle())->firstWhere('kunci', $key);
    }

    /**
     * The counters serving on a date: the office counter, and the units,
     * outlets and temporary counters scheduled and shown that day.
     *
     * @return list<array{kunci: string, nama: string, jenis: string}>
     */
    public function openOn(Carbon $date): array
    {
        $scheduled = MobileServiceStop::query()
            ->active()
            ->within($date, $date)
            ->get()
            ->filter(fn (MobileServiceStop $stop): bool => $stop->occursOn($date))
            ->map(fn (MobileServiceStop $stop): array => $this->place($stop))
            ->pluck('kunci')
            ->all();

        return array_values(array_filter(
            $this->handle(),
            fn (array $counter): bool => $counter['kunci'] === self::OFFICE || in_array($counter['kunci'], $scheduled, true),
        ));
    }

    /**
     * The office counter and the Samsat Keliling units.
     *
     * @return list<array{kunci: string, nama: string, jenis: string}>
     */
    private function fixed(): array
    {
        return [
            ['kunci' => self::OFFICE, 'nama' => 'Loket SAMSAT', 'jenis' => 'kantor'],
            ...array_map(fn (string $unit): array => ['kunci' => "keliling-{$unit}", 'nama' => "Samsat Keliling {$unit}", 'jenis' => 'keliling'], MobileServiceStop::UNITS),
        ];
    }

    /**
     * The counter a schedule row belongs to.
     *
     * @return array{kunci: string, nama: string, jenis: string}
     */
    private function place(MobileServiceStop $stop): array
    {
        $kind = $stop->jenis->value;

        return $stop->jenis === ServicePointKind::Keliling
            ? ['kunci' => "keliling-{$stop->unit}", 'nama' => $stop->name(), 'jenis' => $kind]
            : ['kunci' => $kind.':'.Str::slug($stop->nama), 'nama' => $stop->nama, 'jenis' => $kind];
    }
}
