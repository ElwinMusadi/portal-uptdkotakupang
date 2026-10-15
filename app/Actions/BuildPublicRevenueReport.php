<?php

namespace App\Actions;

use Illuminate\Support\Carbon;

/**
 * The revenue report on the public portal (D-048, D-049): only imports an
 * Administrator has shown on the portal, as fixed tables no visitor can
 * combine to single out a transaction. A day shows its totals only; a month
 * also shows each day and its groups per counter, registration type, vehicle,
 * kecamatan and kelurahan, with their transactions and sum only. No figure
 * describes fewer than three transactions: such groups and days are merged or
 * hidden together with the smallest shown ones when the rest would give them
 * away, and the same days stay hidden in the daily report.
 */
class BuildPublicRevenueReport
{
    /**
     * The breakdowns of a month.
     *
     * @var list<string>
     */
    public const array DIMENSIONS = ['loket', 'jenisDaftar', 'jenisKendaraan', 'kecamatan', 'kelurahan'];

    /**
     * Fewest transactions a published figure may describe.
     */
    public const int MINIMUM = 3;

    /**
     * The name of the row merging the groups below the minimum.
     */
    public const string MASKED = 'Lainnya (disamarkan)';

    /**
     * The registration type of new vehicles.
     */
    public const string NEW_VEHICLES = 'BARU';

    public function __construct(private BuildRevenueReport $report) {}

    /**
     * The report of a day or a whole month, by default the latest day with
     * published transactions.
     *
     * @return array<string, mixed>
     */
    public function handle(?Carbon $from, ?Carbon $until): array
    {
        $report = $this->report->published();
        $dates = $report->dates();

        if ($dates === null) {
            return [
                'tersedia' => null,
                'periode' => null,
                'dari' => null,
                'sampai' => null,
                'ringkasan' => null,
                'kendaraanBaru' => null,
                'disamarkan' => false,
                'pembanding' => null,
                'rincian' => [],
                'minimum' => self::MINIMUM,
            ];
        }

        $from ??= Carbon::createFromFormat('!Y-m-d', $dates['akhir']);
        $until ??= $from->copy();
        $daily = $from->equalTo($until);
        $totals = $report->totals($from, $until);
        $hidden = $daily ? $this->hiddenDay($report, $from) : $this->tooFew($totals['transaksi']);

        $breakdown = [];
        $newVehicles = null;
        if (! $hidden) {
            $newVehicles = $this->figures($report->totals($from, $until, ['jenisDaftar' => [self::NEW_VEHICLES]]));
            if (! $daily) {
                $breakdown = $this->tables($report, $from, $until);
                $shownTypes = array_column(array_filter($breakdown['jenisDaftar'], fn (array $row): bool => ! isset($row['disamarkan'])), 'nilai');
                if ($newVehicles['transaksi'] > 0 && ! in_array(self::NEW_VEHICLES, $shownTypes, true)) {
                    $newVehicles = null;
                }
            }
        }

        $comparison = $hidden ? null : $report->comparison($from, $until);
        $comparisonHidden = $comparison !== null && ($daily
            ? $this->hiddenDay($report, Carbon::createFromFormat('!Y-m-d', $comparison['dari']))
            : $this->tooFew($comparison['ringkasan']['transaksi']));

        return [
            'tersedia' => $dates,
            'periode' => $daily ? 'harian' : 'bulanan',
            'dari' => $from->toDateString(),
            'sampai' => $until->toDateString(),
            'ringkasan' => $hidden ? null : $totals,
            'kendaraanBaru' => $newVehicles,
            'disamarkan' => $hidden,
            'pembanding' => $comparison === null ? null : [
                'dari' => $comparison['dari'],
                'sampai' => $comparison['sampai'],
                'ringkasan' => $comparisonHidden ? null : $this->figures($comparison['ringkasan']),
            ],
            'rincian' => $breakdown,
            'minimum' => self::MINIMUM,
        ];
    }

    /**
     * The groups and days of a month, those below the minimum merged into one
     * last row or kept without their figures.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private function tables(BuildRevenueReport $report, Carbon $from, Carbon $until): array
    {
        $groups = $report->breakdown($from, $until, [], self::DIMENSIONS);
        $tables = [];
        foreach (['loket', 'jenisDaftar', 'jenisKendaraan', 'kecamatan'] as $dimension) {
            $tables[$dimension] = $this->merge($groups[$dimension], $dimension, ...$this->split($groups[$dimension]));
        }

        // A kecamatan's kelurahan are hidden with it, and otherwise hide enough
        // of each other that the kecamatan's total does not give one away
        [, $maskedKecamatan] = $this->split($groups['kecamatan']);
        $tables['kelurahan'] = $this->merge(
            $groups['kelurahan'],
            'kelurahan',
            ...$this->splitWithin($groups['kelurahan'], array_column($maskedKecamatan, 'nilai')),
        );
        $tables['tanggal'] = $this->maskDays($report->days($from, $until));

        return $tables;
    }

    /**
     * Whether a day's report is hidden: the day is among those hidden in its
     * month, so the month's total cannot give it away.
     */
    private function hiddenDay(BuildRevenueReport $report, Carbon $day): bool
    {
        [, $masked] = $this->split($report->days($day->copy()->startOfMonth(), $day->copy()->endOfMonth()->startOfDay()));

        return in_array($day->toDateString(), array_column($masked, 'nilai'), true);
    }

    /**
     * Whether a figure of this many transactions must not be published: some,
     * but fewer than the minimum.
     */
    private function tooFew(int $transactions): bool
    {
        return $transactions > 0 && $transactions < self::MINIMUM;
    }

    /**
     * The figures a visitor sees of a group: its transactions and sum.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function figures(array $row): array
    {
        return array_intersect_key($row, array_flip(['nilai', 'nama', 'kecamatan', 'transaksi', 'jumlah']));
    }

    /**
     * The shown groups of a breakdown in their order, then the masked ones
     * merged into one row.
     *
     * @param  list<array<string, mixed>>  $rows  largest first
     * @param  list<array<string, mixed>>  $shown
     * @param  list<array<string, mixed>>  $masked
     * @return list<array<string, mixed>>
     */
    private function merge(array $rows, string $dimension, array $shown, array $masked): array
    {
        $kept = array_map($this->key(...), $shown);
        $table = array_values(array_map(
            $this->figures(...),
            array_filter($rows, fn (array $row): bool => in_array($this->key($row), $kept, true)),
        ));

        if ($masked === []) {
            return $table;
        }

        return [...$table, [
            'nilai' => self::MASKED,
            ...($dimension === 'loket' ? ['nama' => self::MASKED] : []),
            ...($dimension === 'kelurahan' ? ['kecamatan' => null] : []),
            'transaksi' => array_sum(array_column($masked, 'transaksi')),
            'jumlah' => array_sum(array_column($masked, 'jumlah')),
            'disamarkan' => true,
            'kelompok' => count($masked),
        ]];
    }

    /**
     * The daily totals, the days hidden without their figures.
     *
     * @param  list<array<string, mixed>>  $days  in date order
     * @return list<array<string, mixed>>
     */
    private function maskDays(array $days): array
    {
        [, $masked] = $this->split($days);
        $hidden = array_column($masked, 'nilai');

        return array_map(
            fn (array $day): array => in_array($day['nilai'], $hidden, true)
                ? ['nilai' => $day['nilai'], 'disamarkan' => true]
                : $this->figures($day),
            $days,
        );
    }

    /**
     * The kelurahan that may be shown and those to hide, kecamatan by
     * kecamatan: all of a hidden kecamatan, and otherwise as in split().
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $hiddenKecamatan
     * @return array{list<array<string, mixed>>, list<array<string, mixed>>}
     */
    private function splitWithin(array $rows, array $hiddenKecamatan): array
    {
        $shown = [];
        $masked = [];
        foreach (array_unique(array_column($rows, 'kecamatan')) as $kecamatan) {
            $group = array_values(array_filter($rows, fn (array $row): bool => $row['kecamatan'] === $kecamatan));
            [$groupShown, $groupMasked] = in_array($kecamatan, $hiddenKecamatan, true) ? [[], $group] : $this->split($group);
            $shown = [...$shown, ...$groupShown];
            $masked = [...$masked, ...$groupMasked];
        }

        return [$shown, $masked];
    }

    /**
     * The rows that may be shown and those to hide. Rows below the minimum are
     * hidden; while they describe fewer than the minimum together, the
     * smallest shown row joins them, since the total would give them away.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array{list<array<string, mixed>>, list<array<string, mixed>>}
     */
    private function split(array $rows): array
    {
        $shown = array_values(array_filter($rows, fn (array $row): bool => $row['transaksi'] >= self::MINIMUM));
        $masked = array_values(array_filter($rows, fn (array $row): bool => $row['transaksi'] < self::MINIMUM));

        usort($shown, fn (array $a, array $b): int => [$b['transaksi'], $b['jumlah']] <=> [$a['transaksi'], $a['jumlah']]);
        while ($masked !== [] && $shown !== [] && array_sum(array_column($masked, 'transaksi')) < self::MINIMUM) {
            $masked[] = array_pop($shown);
        }

        return [$shown, $masked];
    }

    /**
     * What tells two rows of a breakdown apart.
     *
     * @param  array<string, mixed>  $row
     */
    private function key(array $row): string
    {
        return $row['nilai'].'|'.($row['kecamatan'] ?? '');
    }
}
