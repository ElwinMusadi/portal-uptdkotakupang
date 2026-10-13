<?php

namespace App\Actions;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\ScheduleAdjustment;
use App\Models\Setting;
use Illuminate\Database\QueryException;

class ShowPublicOpeningHours
{
    /**
     * Time zone of the office; "today" for adjustments is the date in Kupang.
     */
    private const string TIME_ZONE = 'Asia/Makassar';

    /**
     * Build the public opening hours: the weekly hours (none until they are
     * first saved), the notes for citizens, and the adjustments from today
     * on. A source that cannot be read fails the request instead of
     * returning hours that may be wrong.
     *
     * @return array{hari: list<array{hari: int, buka: bool, mulai: string, selesai: string, catatan: string}>, batasBerkasMenit: int, catatan: string, pemberitahuan: string, penyesuaian: list<array{tanggal: string, jenis: string, mulai: string, selesai: string, keterangan: string}>}
     *
     * @throws PublicContentUnavailableException
     */
    public function handle(): array
    {
        try {
            $hours = Setting::query()->find(Setting::OPENING_HOURS)?->value;
            $adjustments = ScheduleAdjustment::query()
                ->where('tanggal', '>=', now(self::TIME_ZONE)->toDateString())
                ->orderBy('tanggal')
                ->limit(100)
                ->get(['tanggal', 'jenis', 'mulai', 'selesai', 'keterangan']);
        } catch (QueryException $exception) {
            throw new PublicContentUnavailableException('The opening hours could not be read.', previous: $exception);
        }

        return [
            'hari' => $hours['hari'] ?? [],
            'batasBerkasMenit' => $hours['batasBerkasMenit'] ?? 0,
            'catatan' => $hours['catatan'] ?? '',
            'pemberitahuan' => $hours['pemberitahuan'] ?? '',
            'penyesuaian' => $adjustments
                ->map(fn (ScheduleAdjustment $adjustment): array => [
                    'tanggal' => $adjustment->tanggal,
                    'jenis' => $adjustment->jenis,
                    'mulai' => $adjustment->mulai,
                    'selesai' => $adjustment->selesai,
                    'keterangan' => $adjustment->keterangan,
                ])
                ->values()
                ->all(),
        ];
    }
}
