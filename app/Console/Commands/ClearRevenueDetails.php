<?php

namespace App\Console\Commands;

use App\Actions\ClearRevenueDetails as ClearDetails;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Drops the details of the STS transactions older than 7 days (D-079), as
 * every import does too, for the days nothing is imported.
 */
#[Signature('pintu:clear-revenue-details')]
#[Description('Mengosongkan rincian (NOPOL, nama, RT/RW, dll.) transaksi STS yang lebih lama dari 7 hari')]
class ClearRevenueDetails extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ClearDetails $clearDetails): int
    {
        $cleared = $clearDetails->handle();
        $this->info("Rincian {$cleared} transaksi dikosongkan.");

        return self::SUCCESS;
    }
}
