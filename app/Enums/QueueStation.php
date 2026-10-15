<?php

namespace App\Enums;

/**
 * The stations that mark a queue ticket's stage done rather than call it
 * (Antrean Online slice 3, D-051): by scanning the QR on the documents or
 * choosing the number from their list. Server is the Penetapan officer's
 * colleague who changes the vehicle's data when Penetapan asks for it; the
 * documents then go back to Penetapan.
 */
enum QueueStation: string
{
    case Penetapan = 'penetapan';
    case Server = 'server';
    case Pelunasan = 'pelunasan';
    case CetakSkpd = 'cetak-skpd';
    case CetakStnk = 'cetak-stnk';

    /**
     * The stage the station works at.
     */
    public function stage(): QueueStage
    {
        return match ($this) {
            self::Penetapan, self::Server => QueueStage::Penetapan,
            self::Pelunasan => QueueStage::Pembayaran,
            self::CetakSkpd => QueueStage::CetakSkpd,
            self::CetakStnk => QueueStage::CetakStnk,
        };
    }

    /**
     * The statuses of the tickets the station lists at its stage: Penetapan
     * also lists those out for a data change, Server only those.
     *
     * @return list<QueueTicketStatus>
     */
    public function statuses(): array
    {
        return match ($this) {
            self::Penetapan => [QueueTicketStatus::Menunggu, QueueTicketStatus::PerubahanData],
            self::Server => [QueueTicketStatus::PerubahanData],
            default => [QueueTicketStatus::Menunggu],
        };
    }
}
