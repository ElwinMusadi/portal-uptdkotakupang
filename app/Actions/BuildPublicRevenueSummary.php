<?php

namespace App\Actions;

use Illuminate\Support\Carbon;

class BuildPublicRevenueSummary
{
    public function __construct(private BuildRevenueReport $report) {}

    /**
     * The homepage's summary of the revenue report (D-055): the year of the
     * latest published day, month by month, with its total over the months
     * shown. As on the Laporan Penerimaan page, a month of 1–2 transactions
     * is hidden; it is also left out of the total, so the total cannot give
     * it away. Months outside the published dates have no figures.
     *
     * @return array{tersedia: bool, tahun: ?string, dari: ?string, sampai: ?string, ringkasan: ?array<string, int>, bulan: list<array{bulan: string, transaksi: ?int, jumlah: ?int, disamarkan?: true}>}
     */
    public function handle(): array
    {
        $report = $this->report->published();
        $dates = $report->dates();

        if ($dates === null) {
            return ['tersedia' => false, 'tahun' => null, 'dari' => null, 'sampai' => null, 'ringkasan' => null, 'bulan' => []];
        }

        $last = Carbon::createFromFormat('!Y-m-d', $dates['akhir']);
        $first = Carbon::createFromFormat('!Y-m-d', $dates['awal'])->max($last->copy()->startOfYear());
        $total = null;
        $months = [];

        foreach (range(1, 12) as $number) {
            $start = Carbon::create($last->year, $number, 1);
            $end = $start->copy()->endOfMonth()->startOfDay();
            $month = $start->format('Y-m');

            if ($end->lt($first) || $start->gt($last)) {
                $months[] = ['bulan' => $month, 'transaksi' => null, 'jumlah' => null];

                continue;
            }

            $totals = $report->totals($start, $end);

            if ($totals['transaksi'] > 0 && $totals['transaksi'] < BuildPublicRevenueReport::MINIMUM) {
                $months[] = ['bulan' => $month, 'transaksi' => null, 'jumlah' => null, 'disamarkan' => true];

                continue;
            }

            $months[] = ['bulan' => $month, 'transaksi' => $totals['transaksi'], 'jumlah' => $totals['jumlah']];

            foreach ($totals as $key => $amount) {
                $total[$key] = ($total[$key] ?? 0) + $amount;
            }
        }

        return [
            'tersedia' => true,
            'tahun' => (string) $last->year,
            'dari' => $first->toDateString(),
            'sampai' => $last->toDateString(),
            'ringkasan' => $total,
            'bulan' => $months,
        ];
    }
}
