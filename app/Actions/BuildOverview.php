<?php

namespace App\Actions;

use App\Http\Resources\RevenueTargetResource;
use App\Models\PotentialImport;
use App\Models\RevenueTarget;
use App\Models\Setting;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * The Ikhtisar page (D-099): the dashboard's revenue report of all counters,
 * Form I and the Pemetaan PKB summary, for whoever holds a link, with the
 * dates their data runs through. Only figures: no plate number, name,
 * address or note of any taxpayer, nor the name of any employee. The
 * revenue counts every import, shown on the portal or not, like the
 * dashboard. The periods are counted back from the latest STS date, so the
 * page moves on by itself after each import.
 */
class BuildOverview
{
    /**
     * The revenue breakdowns the page shows; the make and plate colour are
     * left to the dashboard.
     *
     * @var list<string>
     */
    public const array BREAKDOWNS = ['loket', 'jenisDaftar', 'jenisKendaraan', 'kepemilikan', 'uptAsal', 'kecamatan', 'kelurahan'];

    public function __construct(
        private BuildRevenueReport $report,
        private BuildRevenueRealization $realization,
        private BuildPotentialSummary $potentialSummary,
        private ListLatestRevenueDates $latestDates,
        private ListRevenueCounters $counters,
    ) {}

    /**
     * The page for a period counted back from the latest STS date, or for the
     * given range when the period is null.
     *
     * @return array<string, mixed>
     */
    public function handle(?string $period, ?Carbon $from = null, ?Carbon $until = null): array
    {
        $dates = $this->report->dates();
        $latest = $dates === null ? Carbon::parse(now('Asia/Makassar')->toDateString()) : Carbon::parse($dates['akhir']);
        [$from, $until] = $period === null ? [$from, $until] : $this->period($period, $latest);

        return [
            'periode' => $period ?? 'rentang',
            'dari' => $from->toDateString(),
            'sampai' => $until->toDateString(),
            'pembaruan' => $this->updates($dates, $latest),
            'penerimaan' => $this->revenue($from, $until),
            'realisasi' => $this->realization($until),
            'pemetaan' => $this->potential(),
        ];
    }

    /**
     * The first and last day of a period counted back from the latest STS
     * date: that day, its month through it, the whole month before, or its
     * year through it.
     *
     * @return array{Carbon, Carbon}
     */
    private function period(string $period, Carbon $latest): array
    {
        return match ($period) {
            'hari-terakhir' => [$latest->copy(), $latest->copy()],
            'bulan-lalu' => [$latest->copy()->startOfMonth()->subMonthNoOverflow(), $latest->copy()->startOfMonth()->subDay()],
            'tahun-ini' => [$latest->copy()->startOfYear(), $latest->copy()],
            default => [$latest->copy()->startOfMonth(), $latest->copy()],
        };
    }

    /**
     * How fresh the figures are: the latest STS date, the dates with STS
     * data, each counter's latest date, the counters scheduled on the latest
     * date without any of its transactions yet, and the Data Potensi in use.
     *
     * @param  ?array{awal: string, akhir: string}  $dates
     * @return array<string, mixed>
     */
    private function updates(?array $dates, Carbon $latest): array
    {
        $counters = $this->latestDates->handle();
        $current = array_column(array_filter($counters, fn (array $counter): bool => $counter['sampaiTanggal'] >= $latest->toDateString()), 'nilai');
        $import = PotentialImport::current();

        return [
            'stsTerakhir' => $dates['akhir'] ?? null,
            'tersedia' => $dates,
            'loket' => $counters,
            'belumDiimpor' => $dates === null ? [] : array_values(array_filter(
                $this->counters->openOn($latest),
                fn (array $counter): bool => ! in_array($counter['kunci'], $current, true),
            )),
            'dataPotensi' => $import === null ? null : [
                'tanggal' => $import->tanggal_penetapan?->toDateString(),
                'selesai' => $import->selesai_pada?->toIso8601String(),
            ],
        ];
    }

    /**
     * The revenue report of the period, as the dashboard sums it.
     *
     * @return array<string, mixed>
     */
    private function revenue(Carbon $from, Carbon $until): array
    {
        return [
            'ringkasan' => $this->report->totals($from, $until),
            'pembanding' => $this->report->comparison($from, $until),
            'rincian' => [
                ...$this->report->breakdown($from, $until, [], self::BREAKDOWNS),
                'tanggal' => $this->report->days($from, $until),
            ],
            'loketBuka' => $from->equalTo($until) ? $this->counters->openOn($from) : [],
        ];
    }

    /**
     * Form I of the month the period ends in, through its last day unless
     * that is the end of the month, with the year's target without who saved
     * it.
     *
     * @return array<string, mixed>
     */
    private function realization(Carbon $until): array
    {
        $target = RevenueTarget::query()->firstWhere('tahun', $until->year);

        return [
            ...$this->realization->handle($until->year, $until->month, $target, $until->isLastOfMonth() ? null : $until),
            'target' => $target === null ? null : Arr::except((new RevenueTargetResource($target))->resolve(), ['diperbarui', 'oleh']),
        ];
    }

    /**
     * The Pemetaan PKB summary of the Data Potensi in use, of every due
     * month, with the region-table columns an Administrator chose. The
     * outcomes of the door-to-door visits are left out, as they can hold an
     * officer's note.
     *
     * @return ?array<string, mixed>
     */
    private function potential(): ?array
    {
        $import = PotentialImport::current();

        if ($import === null) {
            return null;
        }

        return [
            'tanggal' => $import->tanggal_penetapan?->toDateString(),
            'selesai' => $import->selesai_pada?->toIso8601String(),
            ...Arr::except($this->potentialSummary->handle($import, null), ['kunjungan']),
            'kolom' => Setting::potentialMapColumns()['wilayah'],
        ];
    }
}
