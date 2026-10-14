<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The structure the portal's Struktur Organisasi page showed before the
 * module moved to the server (D-033), as set out in the Renstra BPAD
 * 2025-2029 (Pergub NTT No. 96 Tahun 2023, D-029). The head's name was
 * given by the owner; the other officials' names are filled in once
 * verified.
 */
return new class extends Migration
{
    /**
     * The initial units' names.
     *
     * @var list<string>
     */
    private const array NAMES = [
        'Kepala UPTD',
        'Subbagian Tata Usaha',
        'Seksi Penetapan dan Penagihan',
        'Seksi Verifikasi',
        'Kelompok Jabatan Fungsional',
        'Polri · Satlantas',
        'PT Jasa Raharja',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();
        $head = Str::lower((string) Str::ulid());
        $partner = 'Mitra satu atap Samsat · garis koordinasi, bukan garis komando.';

        $units = [
            [$head, 'KU', 'pimpinan', null, 'Kepala UPTD Pendapatan Daerah Wilayah Kota Kupang', 'Remmy Christian Pah, S.STP, M.Si', [], 'Memimpin, mengoordinasikan, dan mengawasi pelaksanaan tugas teknis operasional pendapatan daerah di wilayah Kota Kupang, serta menyetujui informasi sebelum dipublikasikan.', ''],
            [Str::lower((string) Str::ulid()), 'TU', 'tata-usaha', $head, 'Kepala Subbagian', '', ['Pengelola kepegawaian', 'Pengelola keuangan', 'Pengelola barang & perlengkapan', 'Penata kelola sistem & teknologi informasi'], 'Merencanakan dan melaksanakan kegiatan ketatausahaan meliputi urusan kepegawaian, keuangan, perlengkapan, dan tata usaha umum, serta memberikan pelayanan administratif kepada semua unsur di lingkungan UPTD.', 'Pengelolaan portal PINTU berada di bawah unit ini.'],
            [Str::lower((string) Str::ulid()), 'S1', 'seksi', $head, 'Kepala Seksi', '', ['Petugas penetapan & korektor', 'Petugas penagihan & Samsat Keliling', 'Kasir & bendahara penerimaan', 'Petugas pembukuan & pelaporan'], 'Merencanakan dan melaksanakan kegiatan penetapan dan penagihan pajak serta pendapatan lain-lain, termasuk pemberian surat keterangan terdaftar untuk kendaraan bermotor.', ''],
            [Str::lower((string) Str::ulid()), 'S2', 'seksi', $head, 'Kepala Seksi', '', ['Petugas pendataan & verifikasi', 'Petugas loket pelayanan'], 'Merencanakan dan melaksanakan kegiatan verifikasi, meliputi pengumpulan data serta pengendalian dan evaluasi pemeriksaan kebenaran penetapan pajak dan pendapatan lain-lain, termasuk pemberian surat keterangan terdaftar kendaraan bermotor.', ''],
            [Str::lower((string) Str::ulid()), 'JF', 'fungsional', $head, '', '', [], 'Melaksanakan tugas sesuai keahlian dan jabatan fungsional masing-masing untuk mendukung pelayanan UPTD.', 'Sesuai formasi yang ditetapkan'],
            [Str::lower((string) Str::ulid()), 'PL', 'mitra', null, '', '', [], 'Registrasi dan identifikasi kendaraan bermotor, termasuk pengesahan STNK.', $partner],
            [Str::lower((string) Str::ulid()), 'JR', 'mitra', null, '', '', [], 'Pengelolaan Sumbangan Wajib Dana Kecelakaan Lalu Lintas Jalan (SWDKLLJ).', $partner],
        ];

        DB::table('organization_units')->insert(array_map(fn (string $nama, array $unit, int $urutan): array => [
            'id' => $unit[0],
            'nama' => $nama,
            'singkatan' => $unit[1],
            'jenis' => $unit[2],
            'induk' => $unit[3],
            'jabatan' => $unit[4],
            'pejabat' => $unit[5],
            'nip' => '',
            'foto' => null,
            'anggota' => json_encode($unit[6]),
            'uraian_tugas' => $unit[7],
            'catatan' => $unit[8],
            'urutan' => $urutan,
            'created_at' => $now,
            'updated_at' => $now,
        ], self::NAMES, $units, array_keys($units)));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('organization_units')->whereIn('nama', self::NAMES)->delete();
    }
};
