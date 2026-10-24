<?php

namespace App\Actions;

use App\Models\RevenueTransaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Rincian penerimaan (D-079): every transaction of a day with the report's
 * filters, in the order of the files, with its details when they are kept.
 */
class BuildRevenueDetails
{
    public function __construct(private BuildRevenueReport $report) {}

    /**
     * The day's transactions, each with its counter's latest name, and how
     * many have no details: imported before details were kept.
     *
     * @param  array<string, list<string>|string>  $filters
     * @return array{transaksi: Collection<int, RevenueTransaction>, belumTercatat: int}
     */
    public function handle(Carbon $day, array $filters): array
    {
        $transactions = BuildRevenueReport::narrow(RevenueTransaction::query()->where('tanggal', $day->toDateString()), $filters)
            ->orderBy('loket')
            ->orderBy('id')
            ->get();
        $names = $this->report->counterNames();
        $transactions->each(fn (RevenueTransaction $transaction) => $transaction->setAttribute('nama_loket', $names[$transaction->loket] ?? $transaction->loket));

        return [
            'transaksi' => $transactions,
            'belumTercatat' => $transactions->filter(fn (RevenueTransaction $transaction): bool => $transaction->rincian === null)->count(),
        ];
    }
}
