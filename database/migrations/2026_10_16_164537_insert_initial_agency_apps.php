<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The apps and websites the owner listed for the Aplikasi & Situs page
 * (D-054), in the owner's order. Their logos came from each app's own
 * source (the Play Store icon, the sites' logos) and are kept, at 240
 * pixels, in public/assets/img/aplikasi.
 */
return new class extends Migration
{
    /**
     * The initial apps: name, full name, description, kind, owner, link and logo.
     *
     * @var list<array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string, 6: string}>
     */
    private const array APPS = [
        ['PINTU', 'Portal Informasi dan Pelayanan UPTD Pendapatan Daerah Wilayah Kota Kupang', 'Situs yang sedang Anda buka: persyaratan layanan, jadwal, berita, dan saluran pengaduan UPTD.', 'situs', 'uptd', 'index.html', 'assets/img/aplikasi/pintu-240.webp'],
        ['PRO NTT', 'Pajak Retribusi Online NTT', 'Bayar Pajak Tahunan kendaraan secara online dari ponsel. Hanya tersedia untuk Android di Google Play.', 'android', 'bpad', 'https://play.google.com/store/apps/details?id=com.iwana.betantt&hl=en', 'assets/img/aplikasi/pro-ntt-240.webp'],
        ['SIKAP', 'Sistem Informasi Kepegawaian dan Arsip Pegawai UPTD Pendapatan Daerah Wilayah Kota Kupang', 'Data kepegawaian dan arsip pegawai UPTD. Khusus pegawai.', 'situs', 'uptd', 'https://sikap.uptdpenda-kupang.web.id', 'assets/img/aplikasi/sikap-240.webp'],
        ['SIPAS', 'Sistem Informasi Pengelolaan Administrasi SKPD', 'Pengelolaan administrasi SKPD. Khusus pegawai.', 'situs', 'uptd', 'https://sipas.uptdpenda-kupang.web.id', 'assets/img/aplikasi/sipas-240.webp'],
        ['Tilang / Razia', 'Razia SAMSAT Kota Kupang', 'Pengecekan administratif kendaraan oleh petugas SAMSAT Kota Kupang saat razia. Khusus petugas.', 'situs', 'uptd', 'https://tilang.uptdpenda-kupang.web.id', 'assets/img/aplikasi/razia-240.webp'],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        DB::table('agency_apps')->insert(array_map(fn (array $app, int $urutan): array => [
            'id' => Str::lower((string) Str::ulid()),
            'nama' => $app[0],
            'nama_lengkap' => $app[1],
            'keterangan' => $app[2],
            'jenis' => $app[3],
            'pengelola' => $app[4],
            'tautan' => $app[5],
            'logo' => $app[6],
            'urutan' => $urutan,
            'created_at' => $now,
            'updated_at' => $now,
        ], self::APPS, array_keys(self::APPS)));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('agency_apps')->whereIn('nama', array_column(self::APPS, 0))->delete();
    }
};
