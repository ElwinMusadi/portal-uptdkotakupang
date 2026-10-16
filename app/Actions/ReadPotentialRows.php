<?php

namespace App\Actions;

use App\Enums\Kecamatan;
use App\Enums\TaxCompliance;
use App\Enums\VehicleWheels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

/**
 * Turns rows of BPAD's potential-tax file into rows of potential_vehicles
 * (D-052). The dashboard reads the file, an HTML table saved as .xls, in the
 * browser and sends its rows as lists of 43 texts in the file's column order.
 * Every column is kept (the owner's decision); the identity card and phone
 * numbers are encrypted. Amounts are written with dots between thousands;
 * texts longer than their column are cut.
 */
class ReadPotentialRows
{
    /**
     * The file's columns, in order, as its header writes them.
     *
     * @var list<string>
     */
    public const array HEADER = ['NOPOL', 'NAMA', 'KTP', 'TELEPON', 'ALAMAT', 'RT', 'RW', 'KELURAHAN', 'KECAMATAN', 'JENIS', 'MERK', 'TIPE',
        'MESIN', 'RANGKA', 'TAHUN BUAT', 'WARNA PLAT', 'WARNA KENDARAAN', 'BBM', 'TGL PENETAPAN', 'UPT ASAL', 'PENGGUNAAN', 'CYL', 'KODE JR',
        'SD STNK', 'SD NOTICE', 'RUBAH BENTUK', 'NJKB', 'NJUB', 'BOBOT', 'BEDA HARI BERJALAN', 'BEDA BULAN BERLAJAN', 'BULAT BULAN BERLAJAN',
        'TAHUN TUNGGAK', 'TAHUN TUNGGAK AWAL', 'POKOK PAJAK', 'DENDA BERJALAN', 'POKOK TUNGGAK', 'DENDA TUNGGAK', 'KEPATUHAN', 'PERINGATAN',
        'STS KENDARAAN DTD', 'KET GIAT DTD', 'PETUGAS DTD'];

    /**
     * The text columns, with their position and longest length.
     *
     * @var array<string, array{int, int}>
     */
    private const array TEXTS = ['nama' => [1, 150], 'alamat' => [4, 255], 'rt' => [5, 10], 'rw' => [6, 10], 'kecamatan_berkas' => [8, 80],
        'jenis' => [9, 40], 'merk' => [10, 60], 'tipe' => [11, 120], 'mesin' => [12, 40], 'rangka' => [13, 40], 'warna_plat' => [15, 20],
        'warna_kendaraan' => [16, 60], 'bbm' => [17, 30], 'upt_asal' => [19, 80], 'penggunaan' => [20, 60], 'kode_jr' => [22, 10],
        'rubah_bentuk' => [25, 80], 'kepatuhan' => [38, 120], 'peringatan' => [39, 200], 'dtd_status' => [40, 200], 'dtd_kegiatan' => [41, 20],
        'dtd_petugas' => [42, 120]];

    /**
     * The whole-number columns, with their position: rupiah amounts written
     * with dots between thousands, and counts.
     *
     * @var array<string, int>
     */
    private const array NUMBERS = ['njkb' => 26, 'njub' => 27, 'beda_hari' => 29, 'beda_bulan' => 30, 'bulat_bulan' => 31, 'tahun_tunggak' => 32,
        'tahun_tunggak_awal' => 33, 'pokok_pajak' => 34, 'denda_berjalan' => 35, 'pokok_tunggak' => 36, 'denda_tunggak' => 37];

    /**
     * The rows ready to save for the import, numbered from the first row's
     * place in the file.
     *
     * @param  list<list<string>>  $rows
     * @return list<array<string, mixed>>
     *
     * @throws ValidationException when a row does not have the file's columns
     */
    public function handle(array $rows, int $first, string $importId): array
    {
        return array_map(function (array $cells, int $index) use ($first, $importId): array {
            if (count($cells) !== count(self::HEADER)) {
                throw ValidationException::withMessages(['baris' => 'Baris '.($first + $index + 1).' tidak berisi '.count(self::HEADER).' kolom seperti berkas Data Potensi.']);
            }

            return $this->row(array_map(fn (mixed $cell): string => trim((string) preg_replace('/\s+/u', ' ', (string) $cell)), $cells), $first + $index + 1, $importId);
        }, $rows, array_keys($rows));
    }

    /**
     * @param  list<string>  $cells
     * @return array<string, mixed>
     */
    private function row(array $cells, int $number, string $importId): array
    {
        $row = ['potential_import_id' => $importId, 'urut' => $number, 'nopol' => mb_substr(strtoupper(str_replace(' ', '', $cells[0])), 0, 12)];

        foreach (self::TEXTS as $column => [$position, $length]) {
            $row[$column] = mb_substr($cells[$position], 0, $length);
        }
        foreach (self::NUMBERS as $column => $position) {
            $row[$column] = (int) preg_replace('/\D/', '', $cells[$position]);
        }

        $kelurahan = mb_strtoupper($cells[7]);
        $compliance = TaxCompliance::fromText($row['kepatuhan']);
        $notice = $this->date($cells[24]);

        return [
            ...$row,
            'ktp' => $cells[2] === '' ? null : Crypt::encryptString($cells[2]),
            'telepon' => $cells[3] === '' ? null : Crypt::encryptString($cells[3]),
            'kelurahan' => mb_substr($kelurahan, 0, 80),
            'kecamatan' => Kecamatan::ofKelurahan($kelurahan)?->value ?? '',
            'roda' => VehicleWheels::fromType($row['jenis'])?->value,
            'tahun_buat' => preg_match('/^\d{4}$/', $cells[14]) === 1 ? (int) $cells[14] : null,
            'tanggal_penetapan' => $this->date($cells[18]),
            'cyl' => preg_match('/^\d+$/', $cells[21]) === 1 ? (int) $cells[21] : null,
            'sd_stnk' => $this->date($cells[23]),
            'sd_notice' => $notice,
            'bulan_jatuh_tempo' => $notice === null ? null : (int) substr($notice, 5, 2),
            'bobot' => preg_match('/^\d+(\.\d+)?$/', $cells[28]) === 1 ? (float) $cells[28] : null,
            'kelompok' => $compliance->value,
            'tagihan' => $compliance->owes() ? $row['pokok_pajak'] + $row['denda_berjalan'] + $row['pokok_tunggak'] + $row['denda_tunggak'] : 0,
        ];
    }

    /**
     * A YYYY-MM-DD date, or null for anything else.
     */
    private function date(string $text): ?string
    {
        return preg_match('/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', $text) === 1 ? $text : null;
    }
}
