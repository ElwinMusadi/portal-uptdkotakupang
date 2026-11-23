<?php

namespace App\Actions;

use App\Models\RevenueImport;
use Illuminate\Support\Str;

class ListLatestRevenueDates
{
    public function __construct(private BuildRevenueReport $report) {}

    /**
     * The last date each counter's STS files have been imported through
     * (D-099), latest first, with the counter's name as its latest import
     * recorded it.
     *
     * @return list<array{nilai: string, nama: string, sampaiTanggal: string}>
     */
    public function handle(): array
    {
        $names = $this->report->counterNames();

        return RevenueImport::query()
            ->toBase()
            ->select('loket')
            ->selectRaw('MAX(tanggal_akhir) AS akhir')
            ->groupBy('loket')
            ->get()
            ->map(fn (object $row): array => [
                'nilai' => $row->loket,
                'nama' => $names[$row->loket] ?? $row->loket,
                'sampaiTanggal' => Str::substr((string) $row->akhir, 0, 10),
            ])
            ->sortBy([['sampaiTanggal', 'desc'], ['nama', 'asc']])
            ->values()
            ->all();
    }
}
