<?php

namespace Tests\Support;

use Illuminate\Http\UploadedFile;

/**
 * Builds STS reports shaped like BPAD's export (an HTML table saved as .xls,
 * D-047) with made-up transactions, so tests never need real taxpayer data.
 */
class StsReport
{
    /**
     * A made-up transaction: a private motorcycle from Oebobo paying PKB and
     * its opsen on 14 October 2026. "jumlah" is the sum of the amounts unless
     * given.
     *
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    public static function row(array $overrides = []): array
    {
        $row = [
            'no' => '1', 'jenis_daftar' => 'Teliti Ulang / Pengesahan', 'nopol' => 'DH1234XY', 'nomorator' => 'A001', 'tanggal' => '2026-10-14',
            'nama' => 'PEMILIK RAHASIA', 'jns_kend' => 'SEPEDA MOTOR', 'tipe' => 'TIPE UJI 125', 'merk' => 'HONDA', 'plat' => 'PUTIH',
            'jt_awal' => '2027-10-14', 'jt_akhir' => '2027-10-14', 'nilai_jual' => '18000000.00',
            'pkb' => '300000', 'bbn' => '0', 'd_pkb' => '0', 'd_bbn' => '0', 'tunggakan' => '0', 'denda_tunggakan' => '0',
            'opsen_pkb' => '198000', 'opsen_bbn' => '0', 'opsen_dpkb' => '0', 'opsen_dbbn' => '0', 'opsen_tpkb' => '0', 'opsen_tdpkb' => '0',
            'tahun_tunggakan' => '0', 'upt_asal' => 'Kota Kupang', 'upt_bayar' => 'Kota Kupang', 'kode' => 'J1',
            'jenis_kendaraan' => 'SEPEDA MOTOR PRIBADI', 'rt' => '007', 'rw' => '003', 'kelurahan' => 'Oebobo', 'kecamatan' => 'OEBOBO', 'ubah_bentuk' => '',
            ...$overrides,
        ];

        if (! array_key_exists('jumlah', $overrides)) {
            $parts = ['pkb', 'bbn', 'd_pkb', 'd_bbn', 'tunggakan', 'denda_tunggakan', 'opsen_pkb', 'opsen_bbn', 'opsen_dpkb', 'opsen_dbbn', 'opsen_tpkb', 'opsen_tdpkb'];
            $row['jumlah'] = (string) array_sum(array_map(fn (string $part): int => (int) $row[$part], $parts));
        }

        return $row;
    }

    /**
     * The report's HTML with these transactions.
     *
     * @param  list<array<string, string>>  $rows
     */
    public static function html(array $rows): string
    {
        $order = ['no', 'jenis_daftar', 'nopol', 'nomorator', 'tanggal', 'nama', 'jns_kend', 'tipe', 'merk', 'plat', 'jt_awal', 'jt_akhir', 'nilai_jual',
            'pkb', 'bbn', 'd_pkb', 'd_bbn', 'tunggakan', 'denda_tunggakan', 'opsen_pkb', 'opsen_bbn', 'opsen_dpkb', 'opsen_dbbn', 'opsen_tpkb', 'opsen_tdpkb',
            'tahun_tunggakan', 'jumlah', 'upt_asal', 'upt_bayar', 'kode', 'jenis_kendaraan', 'rt', 'rw', 'kelurahan', 'kecamatan', 'ubah_bentuk'];
        $body = '';
        foreach ($rows as $row) {
            $body .= "\r\n\t\t<tr>".implode('', array_map(fn (string $column): string => '<td>'.htmlspecialchars($row[$column]).'</td>', $order)).'</tr>';
        }

        return <<<HTML
            <!DOCTYPE html>
            <html>
            <head>
            \t<title></title>
            </head>
            <body>
            \t<style type="text/css">table{border-collapse: collapse;}</style>
            \t<table border="1">
            \t\t<tr><td rowspan="2">NO</td><td rowspan="2">Jenis Daftar</td><td rowspan="2">NOPOL</td><td rowspan="2">NOMORATOR</td><td rowspan="2">TGL_VALIDASI</td><td rowspan="2">NAMA_PEMILIK</td><td rowspan="2">JNS_KEND</td><td rowspan="2">TIPE_KEND</td><td rowspan="2">MERK_KEND</td><td rowspan="2">PLAT</td><td colspan="2">JATUH TEMPO</td><td rowspan="2">NILAI JUAL</td><td colspan="14">REALISASI PENERIMAAN/ PENETAPAN UPT</td><td rowspan="2">UPT ASAL</td><td rowspan="2">UPT BAYAR</td><td rowspan="2">KODE JENIS KENDARAAN</td><td rowspan="2">JENIS KENDARAAN</td><td colspan="4">ALAMAT</td><td rowspan="2">UBAH BENTUK</td></tr>
            \t\t<tr><td>AWAL</td><td>TERAKHIR</td><td>PKB</td><td>BBN</td><td>D.PKB</td><td>D.BBN</td><td>TUNGGAKAN</td><td>DENDA TUNGGAKAN</td><td>OPSEN PKB</td><td>OPSEN BBN</td><td>OPSEN DPKB</td><td>OPSEN DBBN</td><td>OPSEN TPKB</td><td>OPSEN TDPKB</td><td>TAHUN TUNGGAKAN</td><td>JUMLAH</td><td>RT</td><td>RW</td><td>KELURAHAN</td><td>KECAMATAN</td></tr>{$body}
            \t</table>
            </body>
            </html>
            HTML;
    }

    /**
     * The report as an uploaded file.
     *
     * @param  list<array<string, string>>  $rows
     */
    public static function file(array $rows, string $name = 'STS_Harian_Kota_Kupang_2026-10-14.xls'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, self::html($rows));
    }
}
