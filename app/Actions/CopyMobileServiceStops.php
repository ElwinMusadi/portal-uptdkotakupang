<?php

namespace App\Actions;

use App\Models\MobileServiceStop;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CopyMobileServiceStops
{
    /**
     * Copy Samsat Keliling stops to other dates with their unit, times, place
     * and details (D-043, D-044). A stop already scheduled on a date for the
     * same unit, start time and place is not copied there again, so copying
     * twice adds nothing. Call it in a transaction.
     *
     * @param  iterable<int, array{MobileServiceStop, string}>  $plan  each stop with a date (YYYY-MM-DD) to copy it to
     * @param  ?bool  $active  whether the copies are shown on the portal; null keeps each stop's setting
     * @return Collection<int, MobileServiceStop>
     */
    public function handle(iterable $plan, ?bool $active): Collection
    {
        $order = (int) (MobileServiceStop::query()->max('urutan') ?? -1);
        $copies = collect();

        foreach ($plan as [$stop, $date]) {
            if ($this->scheduled($stop, $date)) {
                continue;
            }

            $copy = $stop->replicate(['urutan']);
            $copy->fill([
                'tanggal_mulai' => $date,
                'tanggal_selesai' => $date,
                'hari' => [Carbon::parse($date)->dayOfWeek],
                'aktif' => $active ?? $stop->aktif,
            ]);
            $copy->urutan = ++$order;
            $copy->save();
            $copies->push($copy);
        }

        return $copies;
    }

    /**
     * Whether the stop's unit is already at its place at its start time on
     * the date. Only Samsat Keliling stops have a unit.
     */
    private function scheduled(MobileServiceStop $stop, string $date): bool
    {
        return MobileServiceStop::query()
            ->whereDate('tanggal_mulai', $date)
            ->where('unit', $stop->unit)
            ->where('mulai', $stop->mulai)
            ->where('lokasi', $stop->lokasi)
            ->exists();
    }
}
