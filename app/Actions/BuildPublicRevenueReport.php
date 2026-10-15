<?php

namespace App\Actions;

use Illuminate\Support\Carbon;

/**
 * The revenue report on the public portal (D-048): only imports an
 * Administrator has shown on the portal, the main filters, and no figure that
 * describes fewer than three transactions. Groups below that are merged into
 * one "Lainnya (disamarkan)" row, together with the smallest shown groups
 * when the merged row would itself be too small to hide them; a result below
 * that is not shown at all. Days below that keep their date but lose their
 * figures.
 */
class BuildPublicRevenueReport
{
    /**
     * The filters and breakdowns open to visitors.
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

    public function __construct(private BuildRevenueReport $report) {}

    /**
     * The report of the period, by default the latest day with published
     * transactions.
     *
     * @param  array<string, list<string>>  $filters
     * @return array<string, mixed>
     */
    public function handle(?Carbon $from, ?Carbon $until, array $filters): array
    {
        $report = $this->report->published();
        $dates = $report->dates();

        if ($dates === null) {
            return [
                'tersedia' => null,
                'dari' => null,
                'sampai' => null,
                'ringkasan' => null,
                'disamarkan' => false,
                'pembanding' => null,
                'rincian' => [],
                'pilihan' => array_fill_keys(self::DIMENSIONS, []),
                'minimum' => self::MINIMUM,
            ];
        }

        $from ??= Carbon::createFromFormat('!Y-m-d', $dates['akhir']);
        $until ??= $from->copy();
        $full = $report->handle($from, $until, $filters, self::DIMENSIONS, self::MINIMUM);
        $hidden = $this->tooFew($full['ringkasan']['transaksi']);

        $breakdown = [];
        if (! $hidden) {
            foreach (self::DIMENSIONS as $dimension) {
                $breakdown[$dimension] = $this->merge($full['rincian'][$dimension], $dimension);
            }
            $breakdown['tanggal'] = $this->maskDays($full['rincian']['tanggal']);
        }

        $comparison = $full['pembanding'];

        return [
            'tersedia' => $dates,
            'dari' => $full['dari'],
            'sampai' => $full['sampai'],
            'ringkasan' => $hidden ? null : $full['ringkasan'],
            'disamarkan' => $hidden,
            'pembanding' => $hidden || $comparison === null ? null : [
                ...$comparison,
                'ringkasan' => $this->tooFew($comparison['ringkasan']['transaksi']) ? null : $comparison['ringkasan'],
            ],
            'rincian' => $breakdown,
            'pilihan' => $full['pilihan'],
            'minimum' => self::MINIMUM,
        ];
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
     * The groups of a breakdown, those below the minimum merged into the
     * last row.
     *
     * @param  list<array<string, mixed>>  $rows  largest first
     * @return list<array<string, mixed>>
     */
    private function merge(array $rows, string $dimension): array
    {
        [$shown, $masked] = $this->split($rows);
        if ($masked === []) {
            return $shown;
        }

        $merged = ['nilai' => self::MASKED];
        if ($dimension === 'loket') {
            $merged['nama'] = self::MASKED;
        }
        if ($dimension === 'kelurahan') {
            $merged['kecamatan'] = null;
        }
        foreach ($masked as $row) {
            foreach ($row as $key => $value) {
                if (is_int($value)) {
                    $merged[$key] = ($merged[$key] ?? 0) + $value;
                }
            }
        }

        return [...$shown, [...$merged, 'disamarkan' => true, 'kelompok' => count($masked)]];
    }

    /**
     * The daily totals, the days below the minimum without their figures.
     *
     * @param  list<array<string, mixed>>  $days  in date order
     * @return list<array<string, mixed>>
     */
    private function maskDays(array $days): array
    {
        [, $masked] = $this->split($days);
        $hidden = array_column($masked, 'nilai');

        return array_map(
            fn (array $day): array => in_array($day['nilai'], $hidden, true) ? ['nilai' => $day['nilai'], 'disamarkan' => true] : $day,
            $days,
        );
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

        // Shown rows keep the order they came in
        $key = fn (array $row): string => $row['nilai'].'|'.($row['kecamatan'] ?? '');
        $kept = array_map($key, $shown);

        return [array_values(array_filter($rows, fn (array $row): bool => in_array($key($row), $kept, true))), $masked];
    }
}
