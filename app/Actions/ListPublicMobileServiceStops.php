<?php

namespace App\Actions;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\MobileServiceStop;
use Illuminate\Database\QueryException;

class ListPublicMobileServiceStops
{
    /**
     * Build the public Samsat Keliling schedule: every active stop by day and
     * time, without internal fields. A source that cannot be read fails the
     * request instead of returning an empty schedule.
     *
     * @return list<array{hari: int, mulai: string, selesai: string, lokasi: string, kecamatan: string, layanan: string, catatan: string}>
     *
     * @throws PublicContentUnavailableException
     */
    public function handle(): array
    {
        try {
            $stops = MobileServiceStop::query()
                ->active()
                ->orderBy('hari')
                ->orderBy('mulai')
                ->orderBy('selesai')
                ->orderBy('id')
                ->get(['hari', 'mulai', 'selesai', 'lokasi', 'kecamatan', 'layanan', 'catatan']);
        } catch (QueryException $exception) {
            throw new PublicContentUnavailableException('The Samsat Keliling schedule could not be read.', previous: $exception);
        }

        return $stops
            ->map(fn (MobileServiceStop $stop): array => [
                'hari' => $stop->hari,
                'mulai' => $stop->mulai,
                'selesai' => $stop->selesai,
                'lokasi' => $stop->lokasi,
                'kecamatan' => $stop->kecamatan,
                'layanan' => $stop->layanan,
                'catatan' => $stop->catatan,
            ])
            ->values()
            ->all();
    }
}
