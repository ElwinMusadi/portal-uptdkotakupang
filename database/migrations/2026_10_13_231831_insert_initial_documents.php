<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The documents the portal's Regulasi & Unduhan page listed before the
 * module moved to the server (D-030). Their files were never attached, so
 * they start as drafts without a file or link; the regulations carry their
 * official titles from the Renstra BPAD 2025-2029. The portal shows them
 * once an administrator publishes them with a file or link.
 */
return new class extends Migration
{
    /**
     * The initial documents' titles.
     *
     * @var list<string>
     */
    private const array TITLES = [
        'Undang-Undang Nomor 1 Tahun 2022',
        'Peraturan Daerah Provinsi NTT Nomor 1 Tahun 2024',
        'Peraturan Gubernur NTT Nomor 96 Tahun 2023',
        'Standar Pelayanan UPTD Pendapatan Daerah Wilayah Kota Kupang',
        'Maklumat Pelayanan',
        'SOP Pengelolaan dan Publikasi Informasi PINTU',
        'Formulir surat kuasa',
        'Formulir surat pernyataan STNK hilang/rusak',
        'Laporan Survei Kepuasan Masyarakat Semester I 2026',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        $documents = [
            ['Hubungan Keuangan antara Pemerintah Pusat dan Pemerintahan Daerah', 'regulasi', 'PDF', '1', '2022', 'Subbagian Tata Usaha'],
            ['Pajak Daerah dan Retribusi Daerah · tarif PKB, BBNKB, dan opsen', 'regulasi', 'PDF', '1', '2024', 'Subbagian Tata Usaha'],
            ['Kedudukan, susunan organisasi, tugas dan fungsi, serta tata kerja Badan Pendapatan dan Aset Daerah, termasuk UPTD', 'regulasi', 'PDF', '96', '2023', 'Subbagian Tata Usaha'],
            ['Persyaratan, prosedur, waktu, biaya, produk, dan pengaduan', 'standar', 'PDF', '', '', 'Subbagian Tata Usaha'],
            ['Janji layanan yang ditandatangani Kepala UPTD', 'standar', 'PDF', '', '', 'Subbagian Tata Usaha'],
            ['Alur pengumpulan, verifikasi, persetujuan, publikasi, dan pemutakhiran', 'standar', 'PDF', '', '', 'Subbagian Tata Usaha'],
            ["Untuk perpanjangan 5\u{00A0}tahun dan duplikat STNK · meterai Rp10.000", 'formulir', 'DOCX', '', '', 'Seksi Verifikasi'],
            ['Ditandatangani di atas meterai Rp10.000', 'formulir', 'DOCX', '', '', 'Seksi Verifikasi'],
            ['Indeks kepuasan dan tindak lanjut', 'laporan', 'PDF', '', '2026', 'Subbagian Tata Usaha'],
        ];

        DB::table('documents')->insert(array_map(fn (string $judul, array $document, int $urutan): array => [
            'id' => (string) Str::ulid(),
            'judul' => $judul,
            'keterangan' => $document[0],
            'kategori' => $document[1],
            'format' => $document[2],
            'nomor' => $document[3],
            'tahun' => $document[4],
            'berkas' => null,
            'tautan' => '',
            'urutan' => $urutan,
            'status' => 'draf',
            'penyedia' => $document[5],
            'pemeriksa' => 'Kepala Subbagian Tata Usaha',
            'catatan_revisi' => '',
            'terbit_pada' => null,
            'riwayat' => json_encode([['waktu' => $now->toIso8601String(), 'oleh' => 'PINTU', 'aksi' => 'memasukkan data awal dari halaman Unduhan', 'ke' => 'draf']]),
            'created_at' => $now,
            'updated_at' => $now,
        ], self::TITLES, $documents, array_keys($documents)));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('documents')->whereIn('judul', self::TITLES)->delete();
    }
};
