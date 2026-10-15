<?php

namespace App\Enums;

/**
 * The stages a queue ticket goes through after it is printed at the
 * information desk (Antrean Online, D-051), in the order of the owner's
 * flowchart. Each service follows some of them, always in this order.
 */
enum QueueStage: string
{
    case Pendaftaran = 'pendaftaran';
    case Penetapan = 'penetapan';
    case Kwitansi = 'kwitansi';
    case Pembayaran = 'pembayaran';
    case CetakSkpd = 'cetak-skpd';
    case CetakStnk = 'cetak-stnk';
    case Penyerahan = 'penyerahan';

    /**
     * The stage's name as staff and taxpayers read it.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pendaftaran => 'Loket 1 – Pendaftaran',
            self::Penetapan => 'Penetapan',
            self::Kwitansi => 'Loket Kwitansi',
            self::Pembayaran => 'Pembayaran',
            self::CetakSkpd => 'Cetak SKPD',
            self::CetakStnk => 'Cetak STNK',
            self::Penyerahan => 'Loket 2 – Penyerahan',
        };
    }

    /**
     * Whether counters call numbers to the stage, rather than staff marking
     * it done (D-051).
     */
    public function isCalled(): bool
    {
        return in_array($this, [self::Pendaftaran, self::Kwitansi, self::Penyerahan], true);
    }

    /**
     * What the taxpayer reads while the ticket is at the stage.
     */
    public function instruction(): string
    {
        return match ($this) {
            self::Pendaftaran => 'Tunggu nomor Anda dipanggil di Loket 1 – Pendaftaran.',
            self::Penetapan => 'Berkas Anda sedang ditetapkan.',
            self::Kwitansi => 'Tunggu nomor Anda dipanggil di Loket Kwitansi.',
            self::Pembayaran => 'Silakan membayar di bank dengan kwitansi Anda, lalu tunggu proses pelunasan.',
            self::CetakSkpd => 'Notice pajak (SKPD) Anda sedang dicetak.',
            self::CetakStnk => 'STNK Anda sedang dicetak.',
            self::Penyerahan => 'Tunggu nomor Anda dipanggil di Loket 2 – Penyerahan.',
        };
    }

    /**
     * The given stages in flow order, each once.
     *
     * @param  list<string>  $values
     * @return list<self>
     */
    public static function inOrder(array $values): array
    {
        return array_values(array_filter(self::cases(), fn (self $stage): bool => in_array($stage->value, $values, true)));
    }
}
