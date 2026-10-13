<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The services and requirements the portal's Layanan page showed before the
 * module moved to the server, verified by staff in October 2026 (D-022).
 * They start as published entries, so the portal is never without them.
 */
return new class extends Migration
{
    /**
     * The initial services' slugs, which are the Layanan page's anchors.
     *
     * @var list<string>
     */
    private const array SLUGS = ['pajak-tahunan', 'lima-tahun', 'mutasi-tetap', 'mutasi-bbn', 'duplikat', 'badan-hukum'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();
        $copies = fn (string $dokumen, string $jumlah, int $index): array => ['id' => "p{$index}", 'dokumen' => $dokumen, 'jumlah' => $jumlah];

        $services = [
            ['Pajak tahunan', 'pajak-tahunan', 'Pembayaran PKB dan pengesahan STNK setiap tahun.', 'Loket 1', 'layanan', false, true, true, [], [
                ['Fotokopi STNK dan notis pajak (lampirkan asli)', '2 lembar'],
                ['Fotokopi BPKB halaman 2–3', '2 lembar'],
                ['Fotokopi KTP pemilik (sesuai alamat di STNK dan BPKB)', '2 lembar'],
                ['Map', '1 buah'],
            ], '', true],
            ["Perpanjangan STNK 5\u{00A0}tahun", 'lima-tahun', 'Penggantian STNK dan pelat nomor setiap lima tahun.', 'Loket 2', 'layanan', true, true, true, ['Kuasa: meterai Rp10.000'], [
                ['Fotokopi STNK dan notis pajak (lampirkan asli)', '2 lembar'],
                ['Fotokopi BPKB halaman 2–3', '2 lembar'],
                ['Fotokopi KTP pemilik (sesuai alamat di STNK dan BPKB)', '2 lembar'],
                ['Map', '1 buah'],
                ['Hadirkan kendaraan untuk cek fisik', '—'],
            ], 'Jika dikuasakan, sertakan surat kuasa bertanda tangan di atas meterai Rp10.000 (1 lembar).', true],
            ['Mutasi keluar, pemilik tetap', 'mutasi-tetap', 'Pindah alamat kendaraan ke luar Kota Kupang tanpa ganti pemilik.', 'Loket 3', 'layanan', true, false, false, [], [
                ['Fotokopi KTP alamat tujuan yang baru', '2 lembar'],
                ['Fotokopi STNK dan BPKB (lampirkan yang asli)', '2 lembar'],
                ['Bawa kendaraan untuk cek fisik', '—'],
                ['Map', '1 buah'],
            ], '', true],
            ['Mutasi keluar, ganti pemilik', 'mutasi-bbn', 'Pindah alamat ke luar daerah sekaligus balik nama (BBN).', 'Loket 3', 'layanan', true, false, false, ['Meterai Rp10.000'], [
                ['Fotokopi KTP pemilik kendaraan lama', '2 lembar'],
                ['Fotokopi KTP alamat tujuan yang baru', '2 lembar'],
                ['Fotokopi STNK dan BPKB (lampirkan yang asli)', '2 lembar'],
                ['Bawa kendaraan untuk cek fisik', '—'],
                ['Kuitansi pembelian bertanda tangan di atas meterai Rp10.000', '1 lembar'],
                ['Map', '1 buah'],
            ], 'Kendaraan sudah berada di tempat baru? Ajukan permohonan cek fisik bantuan di Samsat tujuan.', false],
            ['Duplikat STNK hilang atau rusak', 'duplikat', 'Penerbitan ulang STNK yang hilang atau rusak.', 'Loket 2', 'layanan', true, false, true, ['Laporan polisi asli'], [
                ['Surat keterangan STNK hilang/rusak dari kepolisian (asli)', '1 lembar'],
                ['Surat pernyataan pribadi STNK hilang/rusak bermeterai Rp10.000', '1 lembar'],
                ['Fotokopi KTP pemilik (sesuai alamat di STNK dan BPKB)', '2 lembar'],
                ['Fotokopi BPKB halaman 2–3 (lampirkan yang asli)', '2 lembar'],
                ['Map', '1 buah'],
                ['Bawa kendaraan untuk cek fisik', '—'],
            ], 'Jika mewakili, sertakan surat kuasa bertanda tangan di atas meterai Rp10.000 (1 lembar).', false],
            ['Tambahan untuk badan hukum', 'badan-hukum', 'Dokumen tambahan bila kendaraan atas nama perusahaan.', 'Semua loket', 'tambahan', false, false, false, [], [
                ['Fotokopi akta pendirian perusahaan', '1 lembar'],
                ['Fotokopi SITU/SIUP', '1 lembar'],
                ['Fotokopi NIB (Nomor Induk Berusaha)', '1 lembar'],
                ['Fotokopi NPWP', '1 lembar'],
                ['Keterangan domisili perusahaan dari kelurahan setempat', '1 lembar'],
                ['Fotokopi KTP penanggung jawab perusahaan', '1 lembar'],
            ], '', false],
        ];

        DB::table('services')->insert(array_map(fn (array $service, int $urutan): array => [
            'id' => (string) Str::ulid(),
            'nama' => $service[0],
            'slug' => $service[1],
            'ringkasan' => $service[2],
            'loket' => $service[3],
            'jenis' => $service[4],
            'cek_fisik' => $service[5],
            'non_tunai' => $service[6],
            'diwakilkan' => $service[7],
            'label' => json_encode($service[8]),
            'persyaratan' => json_encode(array_map(fn (array $copy, int $index): array => $copies($copy[0], $copy[1], $index + 1), $service[9], array_keys($service[9]))),
            'catatan' => $service[10],
            'unggulan' => $service[11],
            'urutan' => $urutan,
            'status' => 'terbit',
            'penyedia' => 'Seksi Pendataan & Penetapan',
            'pemeriksa' => '',
            'catatan_revisi' => '',
            'terbit_pada' => $now,
            'riwayat' => json_encode([['waktu' => $now->toIso8601String(), 'oleh' => 'PINTU', 'aksi' => 'memasukkan data awal dari halaman Layanan', 'ke' => 'terbit']]),
            'created_at' => $now,
            'updated_at' => $now,
        ], $services, array_keys($services)));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('services')->whereIn('slug', self::SLUGS)->delete();
    }
};
