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
 * the database. The portal's report puts together the same sums over the
 * imports shown on the portal only (D-048, D-049).
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

    /**
     * Whether only the transactions of imports shown on the portal count.
     */
    private bool $publishedOnly = false;

    public function __construct(private ListRevenueCounters $counters) {}

    /**
     * The same report over the imports shown on the portal only (D-048).
     */
    public function published(): static
    {
        $report = clone $this;
        $report->publishedOnly = true;

        return $report;
    }

    /**
     * The report of the period, broken down by every filter.
     *
     * @param  array<string, list<string>|string>  $filters
     * @return array<string, mixed>
     */
    public function handle(Carbon $from, Carbon $until, array $filters): array
    {
        $names = $this->counterNames();

        return [
            'dari' => $from->toDateString(),
            'sampai' => $until->toDateString(),
            'ringkasan' => $this->totals($from, $until, $filters),
            'pembanding' => $this->comparison($from, $until, $filters),
            'rincian' => [
                ...$this->groups($from, $until, $filters, array_keys(self::DIMENSIONS), $names),
                'tanggal' => $this->days($from, $until, $filters),
            ],
            'pilihan' => $this->options($from, $until, $names),
            'loketBuka' => $from->equalTo($until) ? $this->counters->openOn($from) : [],
        ];
    }

    /**
     * The totals of the period broken down by each of the given filters,
     * largest first.
     *
     * @param  array<string, list<string>|string>  $filters
     * @param  list<string>  $dimensions
     * @return array<string, list<array<string, mixed>>>
     */
    public function breakdown(Carbon $from, Carbon $until, array $filters, array $dimensions): array
    {
        return $this->groups($from, $until, $filters, $dimensions, $this->counterNames());
    }

    /**
     * The totals of each day of the period with transactions, in date order.
     *
     * @param  array<string, list<string>|string>  $filters
     * @return list<array<string, mixed>>
     */
    public function days(Carbon $from, Carbon $until, array $filters = []): array
    {
        return $this->transactions($from, $until, $filters)
            ->select('tanggal')
            ->selectRaw($this->sums())
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get()
            ->map(fn (stdClass $row): array => ['nilai' => Str::substr((string) $row->tanggal, 0, 10), ...$this->amounts($row)])
            ->all();
    }

    /**
     * The first and last date with transactions, or null when there is none.
     *
     * @return ?array{awal: string, akhir: string}
     */
    public function dates(): ?array
    {
        $dates = $this->filtered([])->selectRaw('MIN(tanggal) AS awal, MAX(tanggal) AS akhir')->first();

        return $dates?->awal === null ? null : [
            'awal' => Str::substr((string) $dates->awal, 0, 10),
            'akhir' => Str::substr((string) $dates->akhir, 0, 10),
        ];
    }

    /**
     * The transactions of the period matching the filters.
     *
     * @param  array<string, list<string>|string>  $filters
     */
    private function transactions(Carbon $from, Carbon $until, array $filters): Builder
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

        if ($this->publishedOnly) {
            $query->whereIn('revenue_import_id', RevenueImport::query()->where('tampil', true)->select('id'));
        }

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
    public function totals(Carbon $from, Carbon $until, array $filters = []): array
    {
        return $this->amounts($this->transactions($from, $until, $filters)->selectRaw($this->sums())->first());
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
    public function comparison(Carbon $from, Carbon $until, array $filters = []): ?array
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
     * The totals of the period per value of each given filter, largest first.
     *
     * @param  array<string, list<string>|string>  $filters
     * @param  list<string>  $dimensions
     * @param  array<string, string>  $names
     * @return array<string, list<array<string, mixed>>>
     */
    private function groups(Carbon $from, Carbon $until, array $filters, array $dimensions, array $names): array
    {
        $groups = [];
        foreach (array_intersect_key(self::DIMENSIONS, array_flip($dimensions)) as $dimension => $column) {
            $group = $column === 'kelurahan' ? ['kelurahan', 'kecamatan'] : [$column];
            $groups[$dimension] = $this->transactions($from, $until, $filters)
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

        return $groups;
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
            $values = $this->transactions($from, $until, [])->distinct()->orderBy($column)->pluck($column)->all();
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
