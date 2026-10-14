<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The launch article the portal's Informasi page featured before the module
 * moved to the server (D-031). The page's other articles were layout
 * examples and are not kept. It starts as a draft dated the day of the
 * migration, for staff to review before it is published.
 */
return new class extends Migration
{
    private const string SLUG = 'pintu-resmi-diluncurkan';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        DB::table('articles')->insert([
            'id' => (string) Str::ulid(),
            'judul' => 'PINTU resmi diluncurkan. Informasi Samsat kini di satu portal.',
            'slug' => self::SLUG,
            'kategori' => 'berita',
            'ringkasan' => 'Persyaratan, jadwal, dan lokasi Samsat Keliling kini dapat dicek dari rumah. Seluruh isi diverifikasi petugas sebelum terbit.',
            'isi' => '<p>Selama ini, informasi persyaratan layanan Samsat Kota Kupang ditempel pada kertas di meja dan kaca loket. Kertas mudah rusak dan harus diganti terus-menerus. Warga juga perlu bertanya langsung kepada petugas untuk hal yang sebenarnya bisa dibaca sebelum datang.</p><p>PINTU, singkatan dari Portal Informasi dan Pelayanan, hadir untuk menjawab masalah itu. Semua informasi resmi UPTD Pendapatan Daerah Wilayah Kota Kupang kini terhimpun di satu alamat.</p><h2>Apa saja yang tersedia.</h2><ul><li><p>Persyaratan lima layanan utama, lengkap dengan jumlah lembar fotokopi.</p></li><li><p>Jam loket dengan status buka atau tutup secara langsung.</p></li><li><p>Jadwal dan lokasi Samsat Keliling setiap minggu.</p></li><li><p>Berita, pengumuman, dokumentasi kegiatan, serta dokumen regulasi.</p></li><li><p>Formulir masukan dan kanal pengaduan.</p></li></ul><blockquote><p>“Informasi yang sama, untuk semua orang, kapan saja. Itu inti dari PINTU.”</p></blockquote><h2>Bagaimana isinya dijaga.</h2><p>Setiap informasi melewati empat peran sebelum terbit: penyedia data dari unit terkait, pemeriksa, atasan langsung sebagai penyetuju, dan operator sebagai pengelola publikasi. Jadwal Samsat Keliling diperbarui mingguan, sedangkan persyaratan diperbarui setiap ada perubahan regulasi.</p><h2>Sampaikan masukan Anda.</h2><p>Ada informasi yang kurang jelas atau belum tercantum? Kirimkan lewat <a href="kontak.html#masukan">formulir masukan</a>. Masukan pengguna menjadi bahan evaluasi dan pemutakhiran portal.</p>',
            'sampul' => null,
            'keterangan_sampul' => 'Petugas loket mencoba portal PINTU bersama wajib pajak.',
            'kredit_foto' => 'Subbagian Tata Usaha',
            'tanggal' => $now->copy()->setTimezone('Asia/Makassar')->toDateString(),
            'sorotan' => true,
            'pengumuman_beranda' => false,
            'status' => 'draf',
            'penyedia' => 'Pengelola publikasi',
            'pemeriksa' => 'Kepala Subbagian Tata Usaha',
            'catatan_revisi' => '',
            'terbit_pada' => null,
            'riwayat' => json_encode([['waktu' => $now->toIso8601String(), 'oleh' => 'PINTU', 'aksi' => 'memasukkan data awal dari halaman Informasi', 'ke' => 'draf']]),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('articles')->where('slug', self::SLUG)->delete();
    }
};
