<?php

namespace App\Actions;

use App\Models\RevenueTransaction;

/**
 * Drops the details of the STS transactions older than the last 7 days
 * (D-079): the plate number, the owner's name, the RT/RW and the other columns
 * only Rincian penerimaan shows. The amounts, the form numbers and everything
 * else the reports use stay.
 */
class ClearRevenueDetails
{
    /**
     * Clear the details dated before the first day that keeps them, and say
     * how many transactions lost theirs.
     */
    public function handle(): int
    {
        return RevenueTransaction::query()
            ->whereNotNull('rincian')
            ->where('tanggal', '<', RevenueTransaction::detailsSince()->toDateString())
            ->update(['rincian' => null]);
    }
}
