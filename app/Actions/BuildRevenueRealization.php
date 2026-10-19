<?php

namespace App\Actions;

use App\Models\RevenueTarget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use stdClass;

/**
 * The realisation report of a month against the year's target (D-068), laid
 * out as Form I: every counter's STS transactions summed per kind of revenue,
 * before the month ("s.d. bulan lalu"), in it and through it. The month can
 * stop at a given day, as the office's reports made mid-month do (D-070).
 * The months covered by the opening balance entered by hand count that
 * balance instead of their transactions, and have no report of their own.
 */
class BuildRevenueRealization
{
    /**
     * Each kind of revenue as Form I names it, by STS amount.
     *
     * @var array<string, string>
     */
    public const array LABELS = [
        'pkb' => 'PKB',
        'tunggakan' => 'PKB Tunggakan',
        'bbn' => 'BBNKB',
        'denda_pkb' => 'Pendapatan Denda PKB',
        'denda_tunggakan' => 'Pendapatan Denda PKB Tunggakan',
        'denda_bbn' => 'Pendapatan Denda BBNKB',
    ];

    /**
     * The sections of Form I and the targets each one adds up, in order.
     *
     * @var array<string, list<string>>
     */
    private const array SECTIONS = [
        'JUMLAH PAJAK' => ['target_pkb', 'target_bbn'],
        'Jumlah Pendapatan Denda Pajak' => ['target_denda_pkb', 'target_denda_bbn'],
    ];

    /**
     * The row adding up a target's kinds of revenue, for the targets covering
     * more than one.
     *
     * @var array<string, string>
     */
    private const array GROUP_SUMS = [
        'target_pkb' => 'JUMLAH PKB',
        'target_denda_pkb' => 'JUMLAH DENDA PKB',
    ];

    public function __construct(private BuildRevenueReport $report) {}

    /**
     * The report of the month, through the given day of it or the whole
     * month, or only whether it has one when the opening balance covers it.
     *
     * @return array{tahun: int, bulan: int, sampai: ?string, tersedia: bool, sampaiTanggal: ?string, loket: list<array<string, mixed>>, baris: list<array<string, mixed>>}
     */
    public function handle(int $year, int $month, ?RevenueTarget $target, ?Carbon $until = null): array
    {
        $openingMonth = $target?->saldo_sampai_bulan ?? 0;
        $report = ['tahun' => $year, 'bulan' => $month, 'sampai' => $until?->toDateString(), 'tersedia' => $month > $openingMonth,
            'sampaiTanggal' => null, 'loket' => [], 'baris' => []];

        if (! $report['tersedia']) {
            return $report;
        }

        $first = Carbon::create($year, $month);
        $last = $until ?? $first->copy()->endOfMonth()->startOfDay();
        $before = $this->amounts($target, $year, $openingMonth, $month);
        $current = $this->parts($this->report->totals($first, $last));
        $counters = $this->counters($first, $last);

        return [
            ...$report,
            'sampaiTanggal' => $counters === [] ? null : max(array_column($counters, 'sampaiTanggal')),
            'loket' => $counters,
            'baris' => $this->rows($target, $before, $current),
        ];
    }

    /**
     * The realisation before the month: the opening balance and the
     * transactions of the months after it.
     *
     * @return array<string, int>
     */
    private function amounts(?RevenueTarget $target, int $year, int $openingMonth, int $month): array
    {
        $amounts = [];
        foreach (RevenueTarget::PARTS as $part) {
            $amounts[$part] = $openingMonth > 0 ? $target->{"saldo_{$part}"} : 0;
        }

        if ($month - 1 > $openingMonth) {
            $from = Carbon::create($year, $openingMonth + 1);
            $until = Carbon::create($year, $month)->subDay();
            foreach ($this->parts($this->report->totals($from, $until)) as $part => $amount) {
                $amounts[$part] += $amount;
            }
        }

        return $amounts;
    }

    /**
     * The STS amounts the report counts, from the revenue report's totals.
     *
     * @param  array<string, int>  $totals
     * @return array<string, int>
     */
    private function parts(array $totals): array
    {
        $parts = [];
        foreach (RevenueTarget::PARTS as $part) {
            $parts[$part] = $totals[Str::camel($part)];
        }

        return $parts;
    }

    /**
     * The counters with transactions in the month, largest first, with the
     * last date each one has been imported through.
     *
     * @return list<array{nilai: string, nama: string, transaksi: int, jumlah: int, sampaiTanggal: string}>
     */
    private function counters(Carbon $first, Carbon $last): array
    {
        $names = $this->report->counterNames();
        $sum = implode(' + ', array_map(fn (string $part): string => "COALESCE(SUM({$part}), 0)", RevenueTarget::PARTS));

        return DB::table('revenue_transactions')
            ->whereBetween('tanggal', [$first->toDateString(), $last->toDateString()])
            ->select('loket')
            ->selectRaw("COUNT(*) AS transaksi, MAX(tanggal) AS akhir, {$sum} AS jumlah")
            ->groupBy('loket')
            ->get()
            ->map(fn (stdClass $row): array => [
                'nilai' => $row->loket,
                'nama' => $names[$row->loket] ?? $row->loket,
                'transaksi' => (int) $row->transaksi,
                'jumlah' => (int) $row->jumlah,
                'sampaiTanggal' => Str::substr((string) $row->akhir, 0, 10),
            ])
            ->sortBy([['jumlah', 'desc'], ['nama', 'asc']])
            ->values()
            ->all();
    }

    /**
     * The rows of Form I. "baris" is how many rows the number and target
     * cells span: two on the first kind of a target covering two, none on the
     * row under it.
     *
     * @param  array<string, int>  $before
     * @param  array<string, int>  $current
     * @return list<array{jenis: string, no: ?string, uraian: string, baris: int, target: ?int, lalu: int, ini: int, sampaiIni: int, persen: ?float}>
     */
    private function rows(?RevenueTarget $target, array $before, array $current): array
    {
        $rows = [];
        $number = 0;
        $total = $this->sum([]);
        $totalTarget = 0;

        foreach (self::SECTIONS as $sectionLabel => $targetColumns) {
            $section = $this->sum([]);
            $sectionTarget = 0;

            foreach ($targetColumns as $column) {
                $number++;
                $parts = RevenueTarget::TARGETS[$column];
                $amount = $target?->{$column} ?? 0;
                $group = $this->sum([]);

                foreach ($parts as $index => $part) {
                    $item = $this->sum([['lalu' => $before[$part], 'ini' => $current[$part]]]);
                    $shared = count($parts) > 1;
                    $rows[] = $this->row(
                        'rincian',
                        $index === 0 ? (string) $number : null,
                        self::LABELS[$part],
                        $shared ? ($index === 0 ? count($parts) : 0) : 1,
                        $index === 0 ? $amount : null,
                        $item,
                        $shared ? null : $amount,
                    );
                    $group = $this->sum([$group, $item]);
                }

                if (count($parts) > 1) {
                    $rows[] = $this->row('subjumlah', null, self::GROUP_SUMS[$column], 1, $amount, $group, $amount);
                }

                $section = $this->sum([$section, $group]);
                $sectionTarget += $amount;
            }

            $rows[] = $this->row('jumlah', null, $sectionLabel, 1, $sectionTarget, $section, $sectionTarget);
            $total = $this->sum([$total, $section]);
            $totalTarget += $sectionTarget;
        }

        $rows[] = $this->row('total', null, 'TOTAL', 1, $totalTarget, $total, $totalTarget);

        return $rows;
    }

    /**
     * Amounts before and in the month added up.
     *
     * @param  list<array{lalu: int, ini: int}>  $amounts
     * @return array{lalu: int, ini: int}
     */
    private function sum(array $amounts): array
    {
        return [
            'lalu' => array_sum(array_column($amounts, 'lalu')),
            'ini' => array_sum(array_column($amounts, 'ini')),
        ];
    }

    /**
     * A row of Form I, with its share of the target when it has one.
     *
     * @param  array{lalu: int, ini: int}  $amounts
     * @return array{jenis: string, no: ?string, uraian: string, baris: int, target: ?int, lalu: int, ini: int, sampaiIni: int, persen: ?float}
     */
    private function row(string $kind, ?string $number, string $label, int $span, ?int $target, array $amounts, ?int $percentOf): array
    {
        $through = $amounts['lalu'] + $amounts['ini'];

        return [
            'jenis' => $kind,
            'no' => $number,
            'uraian' => $label,
            'baris' => $span,
            'target' => $target,
            'lalu' => $amounts['lalu'],
            'ini' => $amounts['ini'],
            'sampaiIni' => $through,
            'persen' => $percentOf ? round($through / $percentOf * 100, 4) : null,
        ];
    }
}
