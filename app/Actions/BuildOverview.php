<?php

namespace App\Actions;

use App\Http\Resources\RevenueTargetResource;
use App\Models\PotentialImport;
use App\Models\RevenueTarget;
use App\Models\Setting;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * The Ikhtisar page (D-099, D-100): the dashboard's revenue report of all
 * counters with its filters, Form I and the Pemetaan PKB summary, for anyone
 * while an administrator has the page open, with the dates their data runs
 * through. Only figures: no plate number, name, address or note of any
 * taxpayer, nor the name of any employee. The revenue counts every import,
 * shown on the portal or not, like the dashboard. Without a range the page
 * shows the month of the latest STS date through that date, so it moves on
 * by itself after each import. Beside the Data Potensi compliance, the PKB
 * payments at the counters since its date (CountPaymentsSincePotential).
 * The filters narrow the revenue only.
 */
class BuildOverview
{
    /**
     * The revenue breakdowns the page shows; the make and plate colour are
     * only filters there.
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
        private CountPaymentsSincePotential $payments,
    ) {}

    /**
     * The page for the given range, or for the month of the latest STS date
     * (today's, WITA, before any STS) through that date, with the revenue
     * narrowed by the filters.
     *
     * @param  array<string, list<string>|string>  $filters
     * @return array<string, mixed>
     */
    public function handle(?Carbon $from = null, ?Carbon $until = null, array $filters = []): array
    {
        $dates = $this->report->dates();
        $latest = $dates === null ? Carbon::parse(now('Asia/Makassar')->toDateString()) : Carbon::parse($dates['akhir']);
        [$from, $until] = $from === null || $until === null ? [$latest->copy()->startOfMonth(), $latest->copy()] : [$from, $until];
        $import = PotentialImport::current();
        $summary = $import === null ? null : $this->potentialSummary->handle($import, null);

        return [
            'dari' => $from->toDateString(),
            'sampai' => $until->toDateString(),
            'pembaruan' => $this->updates($dates, $latest, $import),
            'penerimaan' => $this->revenue($from, $until, $filters),
            'realisasi' => $this->realization($until),
            'pemetaan' => $import === null ? null : $this->potential($import, $summary),
            // Whatever the period: from the Data Potensi date through the latest STS date
            'pembayaran' => $import === null ? null : $this->payments->handle($import, $summary, $dates['akhir'] ?? null),
        ];
    }

    /**
     * How fresh the figures are: the latest STS date, the dates with STS
     * data, each counter's latest date, the counters scheduled on the latest
     * date without any of its transactions yet, and the Data Potensi in use.
     *
     * @param  ?array{awal: string, akhir: string}  $dates
     * @return array<string, mixed>
     */
    private function updates(?array $dates, Carbon $latest, ?PotentialImport $import): array
    {
        $counters = $this->latestDates->handle();
        $current = array_column(array_filter($counters, fn (array $counter): bool => $counter['sampaiTanggal'] >= $latest->toDateString()), 'nilai');

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
     * The revenue report of the period with the filters, as the dashboard
     * sums it, and the values each filter can take in the period.
     *
     * @param  array<string, list<string>|string>  $filters
     * @return array<string, mixed>
     */
    private function revenue(Carbon $from, Carbon $until, array $filters): array
    {
        return [
            'ringkasan' => $this->report->totals($from, $until, $filters),
            'pembanding' => $this->report->comparison($from, $until, $filters),
            'rincian' => [
                ...$this->report->breakdown($from, $until, $filters, self::BREAKDOWNS),
                'tanggal' => $this->report->days($from, $until, $filters),
            ],
            'pilihan' => $this->report->options($from, $until, $this->report->counterNames()),
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
     * @param  array<string, mixed>  $summary
     * @return array<string, mixed>
     */
    private function potential(PotentialImport $import, array $summary): array
    {
        return [
            'tanggal' => $import->tanggal_penetapan?->toDateString(),
            'selesai' => $import->selesai_pada?->toIso8601String(),
            ...Arr::except($summary, ['kunjungan']),
            'kolom' => Setting::potentialMapColumns()['wilayah'],
        ];
    }
}
