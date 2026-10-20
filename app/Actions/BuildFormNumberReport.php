<?php

namespace App\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The SKPD form numbers used in a period (Nomoratur, D-071), per counter and
 * day: how many forms, the runs of consecutive numbers, the numbers missing
 * between runs close to each other, and the numbers used more than once in
 * the period. Transactions without a form (Mutasi keluar, Tambahan pajak)
 * and those imported before form numbers were kept are counted apart.
 */
class BuildFormNumberReport
{
    /**
     * The widest gap between two runs still counted as missing numbers; a
     * wider jump is taken for another stack of forms.
     */
    public const int GAP_LIMIT = 50;

    public function __construct(private BuildRevenueReport $report) {}

    /**
     * The report of the period, for one counter or all of them.
     *
     * @return array<string, mixed>
     */
    public function handle(Carbon $from, Carbon $until, ?string $counter = null): array
    {
        $names = $this->report->counterNames();
        $rows = DB::table('revenue_transactions')
            ->whereBetween('tanggal', [$from->toDateString(), $until->toDateString()])
            ->when($counter !== null, fn ($query) => $query->where('loket', $counter))
            ->orderBy('tanggal')
            ->orderBy('loket')
            ->orderBy('id')
            ->get(['loket', 'tanggal', 'jenis_daftar', 'nomoratur']);

        $uses = [];
        $byCounterDay = [];
        foreach ($rows as $row) {
            $day = Str::substr((string) $row->tanggal, 0, 10);
            $byCounterDay[$row->loket][$day][] = $row->nomoratur;
            if ($this->isNumber($row->nomoratur)) {
                $uses[$row->nomoratur][] = ['loket' => $names[$row->loket] ?? $row->loket, 'tanggal' => $day, 'jenisDaftar' => $row->jenis_daftar];
            }
        }
        $duplicates = array_filter($uses, fn (array $use): bool => count($use) > 1);

        $counters = [];
        foreach ($byCounterDay as $key => $days) {
            ksort($days);
            $counters[] = $this->counter((string) $key, $names[$key] ?? (string) $key, $days, $duplicates);
        }
        usort($counters, fn (array $a, array $b): int => [$b['lembar'], $a['nama']] <=> [$a['lembar'], $b['nama']]);

        ksort($duplicates, SORT_STRING);

        return [
            'dari' => $from->toDateString(),
            'sampai' => $until->toDateString(),
            'batasTerlewat' => self::GAP_LIMIT,
            'ringkasan' => [
                'lembar' => count($uses),
                'transaksi' => array_sum(array_map('count', $uses)),
                'tanpaNomoratur' => array_sum(array_column($counters, 'tanpaNomoratur')),
                'belumTercatat' => array_sum(array_column($counters, 'belumTercatat')),
                'terlewat' => array_sum(array_column($counters, 'terlewat')),
                'ganda' => count($duplicates),
            ],
            'loket' => $counters,
            'ganda' => array_map(
                fn (string $number, array $use): array => ['nomor' => $number, 'kali' => count($use), 'pemakaian' => $use],
                array_map('strval', array_keys($duplicates)),
                array_values($duplicates),
            ),
        ];
    }

    /**
     * A counter's days and totals.
     *
     * @param  array<string, list<?string>>  $days  the form numbers of each day
     * @param  array<string, list<array<string, string>>>  $duplicates
     * @return array<string, mixed>
     */
    private function counter(string $key, string $name, array $days, array $duplicates): array
    {
        $report = [];
        $numbers = [];
        foreach ($days as $day => $values) {
            $report[] = $this->day($day, $values, $duplicates);
            foreach ($values as $value) {
                if ($this->isNumber($value)) {
                    $numbers[$value] = true;
                }
            }
        }

        return [
            'nilai' => $key,
            'nama' => $name,
            'lembar' => count($numbers),
            'transaksi' => array_sum(array_column($report, 'transaksi')),
            'tanpaNomoratur' => array_sum(array_column($report, 'tanpaNomoratur')),
            'belumTercatat' => array_sum(array_column($report, 'belumTercatat')),
            'terlewat' => array_sum(array_map(fn (array $day): int => array_sum(array_column($day['terlewat'], 'jumlah')), $report)),
            'ganda' => count(array_intersect_key($duplicates, $numbers)),
            'hari' => $report,
        ];
    }

    /**
     * A counter's day: its runs of consecutive numbers, the numbers missing
     * between runs at most GAP_LIMIT apart, and its numbers used more than
     * once in the period.
     *
     * @param  list<?string>  $values
     * @param  array<string, list<array<string, string>>>  $duplicates
     * @return array<string, mixed>
     */
    private function day(string $day, array $values, array $duplicates): array
    {
        $numbers = array_values(array_filter($values, fn (?string $value): bool => $this->isNumber($value)));
        $distinct = array_map('intval', array_unique($numbers));
        sort($distinct);

        $runs = [];
        foreach ($distinct as $number) {
            $last = array_key_last($runs);
            if ($last !== null && $runs[$last][1] === $number - 1) {
                $runs[$last][1] = $number;
            } else {
                $runs[] = [$number, $number];
            }
        }

        $missing = [];
        for ($i = 1; $i < count($runs); $i++) {
            $gap = $runs[$i][0] - $runs[$i - 1][1] - 1;
            if ($gap <= self::GAP_LIMIT) {
                $missing[] = ['awal' => $this->format($runs[$i - 1][1] + 1), 'akhir' => $this->format($runs[$i][0] - 1), 'jumlah' => $gap];
            }
        }

        $used = array_unique($numbers);
        sort($used, SORT_STRING);

        return [
            'tanggal' => $day,
            'lembar' => count($distinct),
            'transaksi' => count($numbers),
            'tanpaNomoratur' => count(array_filter($values, fn (?string $value): bool => $value === '-')),
            'belumTercatat' => count(array_filter($values, fn (?string $value): bool => $value === null)),
            'rentang' => array_map(fn (array $run): array => [
                'awal' => $this->format($run[0]),
                'akhir' => $this->format($run[1]),
                'lembar' => $run[1] - $run[0] + 1,
            ], $runs),
            'terlewat' => $missing,
            'ganda' => array_values(array_filter($used, fn (string $number): bool => isset($duplicates[$number]))),
        ];
    }

    private function isNumber(?string $value): bool
    {
        return $value !== null && $value !== '-';
    }

    /**
     * A form number as printed, seven digits with its leading zeros.
     */
    private function format(int $number): string
    {
        return str_pad((string) $number, ReadStsFile::FORM_NUMBER_DIGITS, '0', STR_PAD_LEFT);
    }
}
