<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The example activities the dashboard and the portal's Dokumentasi page
 * showed before the module moved to the server (D-032). Their photos were
 * illustrations, so they start as drafts without photos, for staff to
 * verify and complete; an activity is published only with a photo.
 */
return new class extends Migration
{
    /**
     * The initial activities' names.
     *
     * @var list<string>
     */
    private const array TITLES = [
        'Samsat Keliling di Kelurahan Oesapa.',
        'Operasi gabungan pemeriksaan pajak kendaraan.',
        'Antrean Samsat Keliling di Kelurahan Sikumana.',
        'Pelayanan Samsat Keliling di Kelurahan Penfui.',
        'Konsultasi alur pengelolaan informasi dengan atasan langsung.',
        'Sosialisasi pajak kendaraan bersama ketua RT.',
        'Gedung Kantor Bersama Samsat Kota Kupang.',
        'Sosialisasi alur pengelolaan konten kepada operator.',
        'Uji coba portal PINTU bersama petugas loket.',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();
        $field = ['Seksi Penetapan dan Penagihan', 'Kepala Seksi Penetapan dan Penagihan'];
        $office = ['Subbagian Tata Usaha', 'Kepala Subbagian Tata Usaha'];

        $activities = [
            ['keliling', 'Kec. Kelapa Lima', '2026-10-21', 'Pelayanan pajak tahunan di Kantor Kelurahan Oesapa.', $field],
            ['operasi', 'Jl. El Tari', '2026-10-15', 'Operasi gabungan bersama Satlantas dan Jasa Raharja.', $field],
            ['keliling', 'Kec. Maulafa', '2026-10-14', '', $field],
            ['keliling', 'Kec. Maulafa', '2026-10-08', '', $field],
            ['internal', 'Ruang Kepala UPTD', '2026-10-06', 'Pembahasan alur pengumpulan, verifikasi, persetujuan, dan publikasi informasi.', $office],
            ['sosialisasi', 'Kec. Alak', '2026-10-02', '', $field],
            ['internal', 'Dokumentasi kantor', '2026-10-01', '', $office],
            ['sosialisasi', 'Aula UPTD', '2026-11-04', 'Rencana kegiatan. Foto ditambahkan setelah kegiatan berlangsung.', $office],
            ['internal', 'Ruang rapat UPTD', '2026-11-18', 'Rencana kegiatan uji fungsi, tampilan, keterbacaan, dan responsivitas portal.', ['Pengelola publikasi', 'Kepala Subbagian Tata Usaha']],
        ];

        DB::table('documentations')->insert(array_map(fn (string $judul, array $activity): array => [
            'id' => (string) Str::ulid(),
            'judul' => $judul,
            'kategori' => $activity[0],
            'lokasi' => $activity[1],
            'tanggal' => $activity[2],
            'deskripsi' => $activity[3],
            'foto' => '[]',
            'status' => 'draf',
            'penyedia' => $activity[4][0],
            'pemeriksa' => $activity[4][1],
            'catatan_revisi' => '',
            'terbit_pada' => null,
            'riwayat' => json_encode([['waktu' => $now->toIso8601String(), 'oleh' => 'PINTU', 'aksi' => 'memasukkan data awal dari halaman Dokumentasi', 'ke' => 'draf']]),
            'created_at' => $now,
            'updated_at' => $now,
        ], self::TITLES, $activities));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('documentations')->whereIn('judul', self::TITLES)->delete();
    }
};
