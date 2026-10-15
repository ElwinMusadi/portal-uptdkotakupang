<?php

namespace App\Actions;

use App\Models\RevenueImport;
use App\Models\RevenueTransaction;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use stdClass;

/**
 * The revenue report of a period (D-047): the totals of the transactions
 * matching the filters, broken down by counter, registration type, vehicle,
 * ownership, plate colour, make, region of origin, kecamatan, kelurahan and
 * date, with the earlier period to compare against. Everything is summed by
 * the database.
 */
class BuildRevenueReport
{
    /**
     * The filters and breakdowns, by request name, with their column.
     *
     * @var array<string, string>
     */
    public const array DIMENSIONS = [
        'loket' => 'loket',
        'jenisDaftar' => 'jenis_daftar',
        'jenisKendaraan' => 'jenis_kendaraan',
        'kepemilikan' => 'kepemilikan',
        'warnaPlat' => 'warna_plat',
        'merk' => 'merk',
        'uptAsal' => 'upt_asal',
        'kecamatan' => 'kecamatan',
        'kelurahan' => 'kelurahan',
    ];

    public function __construct(private ListRevenueCounters $counters) {}

    /**
     * The report of the period.
     *
     * @param  array<string, list<string>|string>  $filters
     * @return array<string, mixed>
     */
    public function handle(Carbon $from, Carbon $until, array $filters): array
    {
        $names = $this->counterNames();
        $breakdown = [];
        foreach (self::DIMENSIONS as $dimension => $column) {
            $group = $column === 'kelurahan' ? ['kelurahan', 'kecamatan'] : [$column];
            $breakdown[$dimension] = $this->query($from, $until, $filters)
                ->select($group)
                ->selectRaw($this->sums())
                ->groupBy($group)
                ->get()
                ->map(fn (stdClass $row): array => [
                    'nilai' => $row->{$column},
                    ...($column === 'loket' ? ['nama' => $names[$row->loket] ?? $row->loket] : []),
                    ...($column === 'kelurahan' ? ['kecamatan' => $row->kecamatan] : []),
                    ...$this->amounts($row),
                ])
                ->sortBy([['jumlah', 'desc'], ['transaksi', 'desc'], ['nilai', 'asc']])
                ->values()
                ->all();
        }

        $breakdown['tanggal'] = $this->query($from, $until, $filters)
            ->select('tanggal')
            ->selectRaw($this->sums())
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get()
            ->map(fn (stdClass $row): array => ['nilai' => Str::substr((string) $row->tanggal, 0, 10), ...$this->amounts($row)])
            ->all();

        return [
            'dari' => $from->toDateString(),
            'sampai' => $until->toDateString(),
            'ringkasan' => $this->totals($from, $until, $filters),
            'pembanding' => $this->comparison($from, $until, $filters),
            'rincian' => $breakdown,
            'pilihan' => $this->options($from, $until, $names),
            'loketBuka' => $from->equalTo($until) ? $this->counters->openOn($from) : [],
        ];
    }

    /**
     * The transactions of the period matching the filters.
     *
     * @param  array<string, list<string>|string>  $filters
     */
    private function query(Carbon $from, Carbon $until, array $filters): Builder
    {
        return $this->filtered($filters)->whereBetween('tanggal', [$from->toDateString(), $until->toDateString()]);
    }

    /**
     * The transactions matching the filters, of any date.
     *
     * @param  array<string, list<string>|string>  $filters
     */
    private function filtered(array $filters): Builder
    {
        $query = DB::table('revenue_transactions');

        foreach (self::DIMENSIONS as $dimension => $column) {
            if (is_array($filters[$dimension] ?? null) && $filters[$dimension] !== []) {
                $query->whereIn($column, $filters[$dimension]);
            }
        }

        match ($filters['tunggakan'] ?? null) {
            'ada' => $query->where('tahun_tunggakan', '>', 0),
            'tidak' => $query->where('tahun_tunggakan', 0),
            default => null,
        };

        return $query;
    }

    /**
     * The number of transactions and the sum of each amount.
     *
     * @param  array<string, list<string>|string>  $filters
     * @return array<string, int>
     */
    private function totals(Carbon $from, Carbon $until, array $filters): array
    {
        return $this->amounts($this->query($from, $until, $filters)->selectRaw($this->sums())->first());
    }

    /**
     * The period to compare against: for a single day, the latest earlier day
     * with transactions; for whole calendar months, as many months right
     * before; for any other period, as many days right before. Null for a day
     * with nothing before it.
     *
     * @param  array<string, list<string>|string>  $filters
     * @return ?array{dari: string, sampai: string, ringkasan: array<string, int>}
     */
    private function comparison(Carbon $from, Carbon $until, array $filters): ?array
    {
        if ($from->equalTo($until)) {
            $day = $this->filtered($filters)->where('tanggal', '<', $from->toDateString())->max('tanggal');
            if ($day === null) {
                return null;
            }
            $start = $end = Carbon::parse(Str::substr((string) $day, 0, 10));
        } elseif ($from->day === 1 && $until->isLastOfMonth()) {
            $start = $from->copy()->subMonthsNoOverflow(($until->year - $from->year) * 12 + $until->month - $from->month + 1);
            $end = $from->copy()->subDay();
        } else {
            $end = $from->copy()->subDay();
            $start = $end->copy()->subDays((int) $from->diffInDays($until));
        }

        return ['dari' => $start->toDateString(), 'sampai' => $end->toDateString(), 'ringkasan' => $this->totals($start, $end, $filters)];
    }

    /**
     * The values each filter can take in the period, whatever the other
     * filters, so the filter lists show what the period holds.
     *
     * @param  array<string, string>  $names
     * @return array<string, list<mixed>>
     */
    private function options(Carbon $from, Carbon $until, array $names): array
    {
        $options = [];
        foreach (self::DIMENSIONS as $dimension => $column) {
            $values = $this->query($from, $until, [])->distinct()->orderBy($column)->pluck($column)->all();
            $options[$dimension] = $column === 'loket'
                ? array_map(fn (string $key): array => ['nilai' => $key, 'nama' => $names[$key] ?? $key], $values)
                : $values;
        }

        return $options;
    }

    /**
     * The name of each counter, as its latest import recorded it.
     *
     * @return array<string, string>
     */
    private function counterNames(): array
    {
        return RevenueImport::query()->orderBy('created_at')->orderBy('id')->pluck('nama_loket', 'loket')->all();
    }

    /**
     * The SELECT list summing the transactions.
     */
    private function sums(): string
    {
        return implode(', ', ['COUNT(*) AS transaksi', ...array_map(
            fn (string $amount): string => "COALESCE(SUM({$amount}), 0) AS {$amount}",
            RevenueTransaction::AMOUNTS,
        )]);
    }

    /**
     * The sums of a result row, with camel-cased names.
     *
     * @return array<string, int>
     */
    private function amounts(?stdClass $row): array
    {
        $amounts = ['transaksi' => (int) ($row->transaksi ?? 0)];
        foreach (RevenueTransaction::AMOUNTS as $amount) {
            $amounts[Str::camel($amount)] = (int) ($row->{$amount} ?? 0);
        }

        return $amounts;
    }
}
