<?php

namespace App\Http\Requests\Dashboard\Concerns;

use App\Actions\LookUpVehicle;
use App\Models\QueueTicket;
use Illuminate\Database\Eloquent\Builder;

/**
 * What staff typed or scanned to find a queue number (D-051), in the `q`
 * field: the ticket's QR (its tracking address or code), its number (A-001,
 * a001, A1) or the plate number.
 */
trait ReadsQueueTicketQuery
{
    /**
     * The tracking code in a scanned QR, if the text holds one.
     */
    public function trackingCode(): ?string
    {
        $pattern = '/(?:^|\/lacak\/)('.QueueTicket::TRACKING_CODE_PATTERN.')(?:[?#].*)?$/';

        return preg_match($pattern, $this->typed(), $match) === 1 ? $match[1] : null;
    }

    /**
     * The queue number typed, written as printed (A-001), if the text is one.
     */
    public function number(): ?string
    {
        return preg_match('/^([A-Za-z])\s*-?\s*(\d{1,4})$/', $this->typed(), $match) === 1
            ? sprintf('%s-%03d', strtoupper($match[1]), (int) $match[2])
            : null;
    }

    /**
     * The text as a plate number: capitals, letters and digits only.
     */
    public function plate(): string
    {
        return LookUpVehicle::normalise($this->typed());
    }

    /**
     * Keep the tickets the text points to: the QR's ticket, the number on
     * any day, or the plate number, whole or, when searching the archive,
     * any part of it.
     *
     * @param  Builder<QueueTicket>  $query
     * @return Builder<QueueTicket>
     */
    public function whereTyped(Builder $query, bool $partOfPlate = false): Builder
    {
        $code = $this->trackingCode();
        $number = $this->number();

        return $query->where(fn (Builder $query) => match (true) {
            $code !== null => $query->where('kode_lacak', $code),
            $number !== null => $query->where('nomor', $number),
            $partOfPlate => $query->where('nopol', 'like', '%'.$this->plate().'%'),
            default => $query->where('nopol', $this->plate()),
        });
    }

    private function typed(): string
    {
        return trim((string) $this->validated('q'));
    }
}
