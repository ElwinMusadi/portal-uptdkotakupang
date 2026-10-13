<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The update standards the dashboard showed before Jadwal Pemutakhiran moved
 * to the server, based on the proposed frequencies in
 * docs/RANCANGAN-UIUX.md section 9.2 (D-025). Managers of the module adjust
 * them in the dashboard.
 */
return new class extends Migration
{
    /**
     * Content, monitored module, owner, frequency and limit in days.
     *
     * @var list<array{string, string, string, string, int}>
     */
    private const array STANDARDS = [
        ['Papan layanan hari ini', 'papan', 'Petugas loket', 'Setiap hari kerja, 07.45 WITA', 1],
        ['Jadwal Samsat Keliling', 'keliling', 'Seksi Penetapan dan Penagihan', 'Mingguan, setiap Jumat', 7],
        ['Dokumentasi kegiatan', 'dokumentasi', 'Semua unit', 'Setiap kegiatan', 14],
        ['Berita & pengumuman', 'berita', 'Pengelola publikasi', 'Minimal 2 per bulan', 15],
        ['Jam pelayanan', 'jam', 'Subbagian Tata Usaha', 'Bulanan', 30],
        ['Persyaratan layanan', 'layanan', 'Seksi Verifikasi', 'Saat regulasi berubah · tinjau tiap 3 bulan', 90],
        ['Regulasi & unduhan', 'dokumen', 'Subbagian Tata Usaha', 'Saat regulasi berubah · tinjau tiap 6 bulan', 180],
        ['Profil instansi', 'profil', 'Subbagian Tata Usaha', 'Tahunan', 365],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        DB::table('update_standards')->insert(array_map(fn (array $standard, int $urutan): array => [
            'id' => (string) Str::ulid(),
            'konten' => $standard[0],
            'modul' => $standard[1],
            'unit' => $standard[2],
            'frekuensi' => $standard[3],
            'interval_hari' => $standard[4],
            'urutan' => $urutan,
            'created_at' => $now,
            'updated_at' => $now,
        ], self::STANDARDS, array_keys(self::STANDARDS)));
    }

    /**
     * Reverse the migrations: remove the initial standards, not the ones
     * added in the dashboard.
     */
    public function down(): void
    {
        foreach (self::STANDARDS as [$konten, $modul]) {
            DB::table('update_standards')->where('konten', $konten)->where('modul', $modul)->delete();
        }
    }
};
