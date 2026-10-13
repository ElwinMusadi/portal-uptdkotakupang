<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The Kebijakan Privasi and Syarat & Ketentuan pages as their markup shows
 * them, in the form the dashboard's editor writes (D-024). They have never
 * been saved, so "updated_at" stays empty and the portal keeps its markup
 * until a page is first saved in the dashboard.
 */
return new class extends Migration
{
    /**
     * The initial pages' slugs.
     *
     * @var list<string>
     */
    private const array SLUGS = ['kebijakan-privasi', 'syarat-ketentuan'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $pages = [
            [
                'kebijakan-privasi',
                'Data Anda secukupnya.',
                'Dijaga sepenuhnya.',
                'Kebijakan privasi · berlaku sejak [tanggal terbit]',
                '<p>Kebijakan ini menjelaskan data apa yang dikumpulkan portal PINTU, untuk apa, dan bagaimana kami melindunginya. Kebijakan mengacu pada Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi.</p>'
                .'<h2>Data yang kami kumpulkan.</h2>'
                .'<ul><li><p>Data yang Anda isi di formulir masukan: nama (opsional), email atau nomor WhatsApp, dan isi pesan.</p></li><li><p>Data teknis anonim: halaman yang dikunjungi dan jenis perangkat, untuk mengukur kebutuhan informasi.</p></li><li><p>Portal tidak meminta NIK, nomor rekening, atau data kendaraan untuk sekadar membaca informasi.</p></li></ul>'
                .'<h2>Untuk apa data dipakai.</h2>'
                .'<p>Untuk menjawab pertanyaan, menindaklanjuti pengaduan, dan mengevaluasi kualitas informasi. Data tidak dijual dan tidak dibagikan kepada pihak lain di luar keperluan penanganan pengaduan.</p>'
                .'<h2>Berapa lama disimpan.</h2>'
                .'<p>Pesan dan pengaduan disimpan selama diperlukan untuk tindak lanjut dan pelaporan, sesuai ketentuan kearsipan yang berlaku.</p>'
                .'<h2>Hak Anda.</h2>'
                .'<p>Anda dapat meminta akses, perbaikan, atau penghapusan data melalui <a href="kontak.html#masukan">formulir kontak</a>.</p>'
                .'<blockquote><p>Petugas kami tidak pernah meminta kata sandi, PIN, atau kode OTP Anda.</p></blockquote>'
                .'<p><em>Draf kebijakan. Wajib ditinjau unit hukum sebelum terbit.</em></p>',
            ],
            [
                'syarat-ketentuan',
                'Informasi resmi.',
                'Dipakai dengan bijak.',
                'Syarat & ketentuan · berlaku sejak [tanggal terbit]',
                '<h2>Sifat informasi.</h2>'
                .'<p>Informasi di portal ini bersifat panduan. Nilai pajak yang mengikat adalah yang tercetak pada notis pajak resmi. Hasil simulasi hanya perkiraan.</p>'
                .'<h2>Pemutakhiran.</h2>'
                .'<p>Setiap halaman mencantumkan tanggal pemutakhiran. Bila terdapat perbedaan dengan papan informasi di loket, informasi terbaru yang telah disetujui pimpinan yang berlaku.</p>'
                .'<h2>Penggunaan konten.</h2>'
                .'<p>Konten boleh dikutip untuk keperluan non-komersial dengan menyebutkan sumber. Lambang daerah tidak boleh diubah atau dipakai untuk kepentingan lain.</p>'
                .'<h2>Tautan pihak ketiga.</h2>'
                .'<p>Portal dapat menautkan ke situs mitra seperti SP4N-LAPOR!, Ombudsman, atau bank mitra. Kebijakan situs tersebut berada di luar tanggung jawab kami.</p>'
                .'<p><em>Draf ketentuan. Wajib ditinjau unit hukum sebelum terbit.</em></p>',
            ],
        ];

        $now = now();

        DB::table('static_pages')->insert(array_map(fn (array $page, int $urutan): array => [
            'id' => (string) Str::ulid(),
            'slug' => $page[0],
            'judul' => $page[1],
            'judul_lanjutan' => $page[2],
            'pengantar' => $page[3],
            'isi' => $page[4],
            'urutan' => $urutan,
            'created_at' => $now,
            'updated_at' => null,
        ], $pages, array_keys($pages)));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('static_pages')->whereIn('slug', self::SLUGS)->delete();
    }
};
