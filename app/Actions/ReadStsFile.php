<?php

namespace App\Actions;

use App\Models\RevenueTransaction;
use Illuminate\Validation\ValidationException;

/**
 * Reads an STS report exported by BPAD's system (D-047): an HTML table saved
 * with an .xls extension, one row per transaction under two header rows. The
 * columns the revenue report needs are checked and kept as they are, and
 * since D-071 the SKPD form number ("nomorator"). Since D-079 the other
 * columns, the plate number, the owner's name and the RT/RW among them, are
 * read as text into "rincian" for the dashboard's Rincian penerimaan, which
 * keeps them for the last 7 days only; only the row number is dropped. The
 * plate number also feeds the keyed "sidik" that recognises a transaction
 * imported twice.
 *
 * The table is read with regular expressions rather than DOMDocument: the
 * export is machine-made, and libxml's memory for a month's file (7,000+ rows)
 * is not bounded by PHP's memory limit.
 */
class ReadStsFile
{
    /**
     * The first header row, upper-cased with single spaces.
     *
     * @var list<string>
     */
    public const array FIRST_HEADER = ['NO', 'JENIS DAFTAR', 'NOPOL', 'NOMORATOR', 'TGL_VALIDASI', 'NAMA_PEMILIK', 'JNS_KEND', 'TIPE_KEND',
        'MERK_KEND', 'PLAT', 'JATUH TEMPO', 'NILAI JUAL', 'REALISASI PENERIMAAN/ PENETAPAN UPT', 'UPT ASAL', 'UPT BAYAR',
        'KODE JENIS KENDARAAN', 'JENIS KENDARAAN', 'ALAMAT', 'UBAH BENTUK'];

    /**
     * The second header row, under the grouped columns.
     *
     * @var list<string>
     */
    public const array SECOND_HEADER = ['AWAL', 'TERAKHIR', 'PKB', 'BBN', 'D.PKB', 'D.BBN', 'TUNGGAKAN', 'DENDA TUNGGAKAN', 'OPSEN PKB',
        'OPSEN BBN', 'OPSEN DPKB', 'OPSEN DBBN', 'OPSEN TPKB', 'OPSEN TDPKB', 'TAHUN TUNGGAKAN', 'JUMLAH', 'RT', 'RW', 'KELURAHAN', 'KECAMATAN'];

    /**
     * The cells of a transaction row.
     */
    private const int CELLS = 36;

    /**
     * The digits of an SKPD form number (D-071).
     */
    public const int FORM_NUMBER_DIGITS = 7;

    /**
     * The column of each amount in a transaction row.
     *
     * @var array<string, int>
     */
    private const array AMOUNT_COLUMNS = ['pkb' => 13, 'bbn' => 14, 'denda_pkb' => 15, 'denda_bbn' => 16, 'tunggakan' => 17,
        'denda_tunggakan' => 18, 'opsen_pkb' => 19, 'opsen_bbn' => 20, 'opsen_denda_pkb' => 21, 'opsen_denda_bbn' => 22,
        'opsen_tunggakan' => 23, 'opsen_denda_tunggakan' => 24, 'jumlah' => 26];

    /**
     * The text columns kept, with their column and longest length.
     *
     * @var array<string, array{int, int}>
     */
    private const array TEXT_COLUMNS = ['jenis_daftar' => [1, 80], 'jenis_kendaraan' => [6, 40], 'merk' => [8, 40], 'warna_plat' => [9, 20],
        'upt_asal' => [27, 80], 'kelurahan' => [33, 80], 'kecamatan' => [34, 80]];

    /**
     * The columns of "rincian" (D-079), in the file's order. They are shown as
     * the file has them, so they are not checked: an empty cell is null.
     *
     * @var array<string, int>
     */
    public const array DETAIL_COLUMNS = ['nopol' => 2, 'nama' => 5, 'tipeKendaraan' => 7, 'jatuhTempoAwal' => 10, 'jatuhTempoAkhir' => 11,
        'nilaiJual' => 12, 'uptBayar' => 28, 'kodeJenisKendaraan' => 29, 'jenisKendaraan' => 30, 'rt' => 31, 'rw' => 32, 'ubahBentuk' => 35];

    /**
     * The file's transactions, in its order.
     *
     * @return list<array<string, int|string|array<string, ?string>>>
     *
     * @throws ValidationException when the file is not an STS report or a row cannot be read
     */
    public function handle(string $html): array
    {
        if (! mb_check_encoding($html, 'UTF-8')) {
            $html = mb_convert_encoding($html, 'UTF-8', 'Windows-1252');
        }

        preg_match_all('~<tr\b[^>]*>(.*?)</tr>~is', $html, $rows);
        $rows = $rows[1];

        if (count($rows) < 2 || $this->header($rows[0]) !== self::FIRST_HEADER || $this->header($rows[1]) !== self::SECOND_HEADER) {
            $this->fail('Berkas ini bukan laporan STS dari sistem BPAD: susunan kolomnya tidak dikenali.');
        }

        $transactions = [];
        foreach (array_slice($rows, 2) as $index => $row) {
            $cells = $this->cells($row);

            if ($cells === [] || implode('', $cells) === '') {
                continue;
            }

            $line = $index + 3;
            if (count($cells) !== self::CELLS) {
                $this->fail("Baris {$line}: ada ".count($cells).' kolom, seharusnya '.self::CELLS.'.');
            }

            $transactions[] = $this->transaction($cells, $line);
        }

        if ($transactions === []) {
            $this->fail('Berkas tidak berisi transaksi.');
        }

        return $transactions;
    }

    /**
     * A transaction row: the report's columns, checked, and the others as
     * "rincian".
     *
     * @param  list<string>  $cells
     * @return array<string, int|string|array<string, ?string>>
     */
    private function transaction(array $cells, int $line): array
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $cells[4], $date) !== 1 || ! checkdate((int) $date[2], (int) $date[3], (int) $date[1])) {
            $this->fail("Baris {$line}: tanggal validasi \"{$cells[4]}\" tidak dikenali.");
        }

        $transaction = ['tanggal' => $cells[4]];
        foreach (self::TEXT_COLUMNS as $name => [$column, $length]) {
            $value = $cells[$column] === '' ? '-' : $cells[$column];
            if (mb_strlen($value) > $length) {
                $this->fail("Baris {$line}: isi kolom {$name} lebih dari {$length} karakter.");
            }
            $transaction[$name] = $value;
        }

        $transaction['nomoratur'] = $this->formNumber($cells[3], $line);
        $transaction['kepemilikan'] = self::ownership($cells[30]);
        $transaction['tahun_tunggakan'] = $this->wholeNumber($cells[25], $line, 'tahun tunggakan', 'angka bulat');
        foreach (self::AMOUNT_COLUMNS as $name => $column) {
            $transaction[$name] = $this->wholeNumber($cells[$column], $line, $name, 'angka rupiah bulat');
        }

        $parts = array_sum(array_map(fn (string $part): int => $transaction[$part], RevenueTransaction::PARTS));
        if ($parts !== $transaction['jumlah']) {
            $this->fail("Baris {$line}: jumlah tidak sama dengan rinciannya.");
        }

        $transaction['sidik'] = self::fingerprint($cells[2], $cells[4], $cells[1], $transaction['jumlah']);
        $transaction['rincian'] = array_map(fn (int $column): ?string => $cells[$column] === '' ? null : $cells[$column], self::DETAIL_COLUMNS);

        return $transaction;
    }

    /**
     * Who owns the vehicle, from its full kind such as "SEPEDA MOTOR PRIBADI"
     * or "MINIBUS PEMERINTAH DAERAH".
     */
    public static function ownership(string $kind): string
    {
        $kind = mb_strtoupper($kind);

        return match (true) {
            str_contains($kind, 'PEMERINTAH') => 'pemerintah',
            str_ends_with($kind, 'PRIBADI') => 'pribadi',
            str_ends_with($kind, 'UMUM') => 'umum',
            default => 'lainnya',
        };
    }

    /**
     * The keyed hash that recognises a transaction: the plate number, the
     * validation date, the registration type and the amount. The key keeps
     * plate numbers from being guessed back from the hash.
     */
    public static function fingerprint(string $plate, string $date, string $type, int $amount): string
    {
        $plate = preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($plate));

        return hash_hmac('sha256', implode('|', [$plate, $date, mb_strtoupper($type), $amount]), (string) config('app.key'));
    }

    /**
     * The SKPD form number, always seven digits (D-071). Excel drops its
     * leading zeros, so a shorter number gets them back: "12345" is
     * "0012345". Transactions without a form, such as Mutasi keluar, have
     * "-".
     */
    private function formNumber(string $value, int $line): string
    {
        if ($value === '' || $value === '-') {
            return '-';
        }

        if (preg_match('/^\d{1,'.self::FORM_NUMBER_DIGITS.'}$/', $value) !== 1) {
            $this->fail("Baris {$line}: nomoratur \"{$value}\" bukan nomor SKPD 7 digit.");
        }

        return str_pad($value, self::FORM_NUMBER_DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * A whole, non-negative number such as "125000" or "125000.00"; empty is zero.
     */
    private function wholeNumber(string $value, int $line, string $name, string $kind): int
    {
        if ($value === '') {
            return 0;
        }

        if (preg_match('/^(\d{1,15})(?:\.(\d+))?$/', $value, $match) !== 1 || ltrim($match[2] ?? '', '0') !== '') {
            $this->fail("Baris {$line}: nilai {$name} \"{$value}\" bukan {$kind}.");
        }

        return (int) $match[1];
    }

    /**
     * The cell texts of a header row, upper-cased with single spaces.
     *
     * @return list<string>
     */
    private function header(string $row): array
    {
        return array_map(fn (string $cell): string => mb_strtoupper($cell), $this->cells($row));
    }

    /**
     * The plain text of each cell of a row.
     *
     * @return list<string>
     */
    private function cells(string $row): array
    {
        preg_match_all('~<t[dh]\b[^>]*>(.*?)</t[dh]>~is', $row, $cells);

        return array_map(
            fn (string $cell): string => trim((string) preg_replace('/[\s\x{00A0}]+/u', ' ', html_entity_decode(strip_tags($cell), ENT_QUOTES | ENT_HTML5, 'UTF-8'))),
            $cells[1],
        );
    }

    /**
     * @throws ValidationException
     */
    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['berkas' => $message]);
    }
}
