<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One dashboard settings document stored on the server, keyed by the
 * dashboard's setting name (D-014). Settings not stored here still live in
 * the browser.
 */
class Setting extends Model
{
    /**
     * The opening hours of the office counters (Jam Pelayanan).
     */
    public const string OPENING_HOURS = 'jam';

    /**
     * The service board on the portal's homepage (Papan Layanan).
     */
    public const string SERVICE_BOARD = 'papan';

    /**
     * The figures of the transparency dashboard on the portal's homepage
     * (Statistik Layanan).
     */
    public const string STATISTICS = 'statistik';

    /**
     * The statistics categories, as the portal's tabs name them.
     *
     * @var list<string>
     */
    public const array STATISTICS_CATEGORIES = ['transaksi', 'penerimaan', 'keliling', 'kepuasan'];

    /**
     * The only statistics status the portal publishes (D-018).
     */
    public const string STATISTICS_VERIFIED = 'terverifikasi';

    /**
     * The office's address, contact details and complaint channels on the
     * portal's footer and Kontak page (Kontak & Kanal).
     */
    public const string CONTACT = 'kontak';

    /**
     * The contact document until it is first saved: empty, so the portal
     * shows no placeholder address or number.
     *
     * @var array{namaKantor: string, alamat: string, kota: string, provinsi: string, telepon: string, whatsapp: string, whatsappCatatan: string, email: string, mediaSosial: list<array{id: string, platform: string, akun: string, url: string}>, mapsUrl: string, catatanLokasi: string, janjiBalasan: string, kanalPengaduan: list<array{id: string, nama: string, deskripsi: string, tautanLabel: string, tautanUrl: string}>}
     */
    public const array UNSAVED_CONTACT = [
        'namaKantor' => '',
        'alamat' => '',
        'kota' => '',
        'provinsi' => '',
        'telepon' => '',
        'whatsapp' => '',
        'whatsappCatatan' => '',
        'email' => '',
        'mediaSosial' => [],
        'mapsUrl' => '',
        'catatanLokasi' => '',
        'janjiBalasan' => '',
        'kanalPengaduan' => [],
    ];

    /**
     * The vision, missions, contributions, core values and service pledge on
     * the portal's Visi & Misi page (Visi & Misi).
     */
    public const string VISION_MISSION = 'visiMisi';

    /**
     * The Visi & Misi document until it is first saved: the official text the
     * portal showed before the page was managed from the dashboard (D-020).
     * Without the signed pledge file, which is uploaded from the dashboard
     * (D-028).
     *
     * @var array{sumberVisi: string, visi: string, misi: list<array{id: string, teks: string, kataKunci: string}>, kontribusi: list<array{id: string, misi: string, judul: string, deskripsi: string}>, nilai: list<array{id: string, huruf: string, judul: string, deskripsi: string}>, maklumat: array{teks: string, penandatangan: string, berkas: null}}
     */
    public const array INITIAL_VISION_MISSION = [
        'sumberVisi' => 'Visi Pemerintah Provinsi NTT',
        'visi' => 'NTT Maju, Sehat, Cerdas, Sejahtera dan Berkelanjutan.',
        'misi' => [
            ['id' => 'misi-1', 'teks' => 'Memastikan infrastruktur berkelanjutan demi mewujudkan ekonomi berbasis potensi daerah yang berdaya saing.', 'kataKunci' => 'Maju'],
            ['id' => 'misi-2', 'teks' => 'Memperluas pelayanan kesehatan dan jaminan sosial yang lebih inklusif, terjangkau, dan mudah diakses.', 'kataKunci' => 'Sehat'],
            ['id' => 'misi-3', 'teks' => 'Menghadirkan pendidikan berkualitas yang merata, partisipatif, dan tepat sasaran.', 'kataKunci' => 'Cerdas'],
            ['id' => 'misi-4', 'teks' => 'Mewujudkan kesejahteraan sosial, kesetaraan akses, serta kualitas hidup yang berkeadilan dan madani bagi seluruh lapisan masyarakat.', 'kataKunci' => 'Sejahtera'],
            ['id' => 'misi-5', 'teks' => 'Mewujudkan pembangunan berkelanjutan melalui pengelolaan sumber daya alam dan manusia yang bijak serta pemenuhan hak asasi manusia untuk masa depan yang inklusif.', 'kataKunci' => 'Berkelanjutan'],
        ],
        'kontribusi' => [
            ['id' => 'kontribusi-1', 'misi' => 'Misi 01 · Maju', 'judul' => 'Tata kelola informasi yang tertib.', 'deskripsi' => 'Alur pengumpulan, verifikasi, persetujuan, dan publikasi informasi menjadi fondasi sistem informasi pelayanan yang berkelanjutan.'],
            ['id' => 'kontribusi-2', 'misi' => 'Misi 04 · Sejahtera', 'judul' => 'Akses informasi yang setara.', 'deskripsi' => 'Persyaratan, jadwal, dan lokasi layanan dapat dibuka siapa saja, kapan saja, tanpa harus bertanya ke loket lebih dulu.'],
        ],
        'nilai' => [
            ['id' => 'nilai-1', 'huruf' => 'B', 'judul' => 'Berorientasi pelayanan', 'deskripsi' => 'Memahami kebutuhan wajib pajak. Ramah, cekatan, dan solutif.'],
            ['id' => 'nilai-2', 'huruf' => 'A', 'judul' => 'Akuntabel', 'deskripsi' => 'Jujur, cermat, disiplin, dan bertanggung jawab atas setiap transaksi.'],
            ['id' => 'nilai-3', 'huruf' => 'K', 'judul' => 'Kompeten', 'deskripsi' => 'Terus belajar agar layanan makin cepat dan tepat.'],
            ['id' => 'nilai-4', 'huruf' => 'H', 'judul' => 'Harmonis', 'deskripsi' => 'Saling menghargai, di antara rekan kerja dan dengan masyarakat.'],
            ['id' => 'nilai-5', 'huruf' => 'L', 'judul' => 'Loyal', 'deskripsi' => 'Berdedikasi dan menjaga rahasia jabatan serta nama baik instansi.'],
            ['id' => 'nilai-6', 'huruf' => 'A', 'judul' => 'Adaptif', 'deskripsi' => 'Terbuka pada perubahan dan inovasi digital.'],
            ['id' => 'nilai-7', 'huruf' => 'K', 'judul' => 'Kolaboratif', 'deskripsi' => 'Bersinergi dengan Polri, Jasa Raharja, dan bank mitra.'],
        ],
        'maklumat' => [
            'teks' => 'Dengan ini, kami menyatakan sanggup menyelenggarakan pelayanan sesuai standar pelayanan yang telah ditetapkan. Apabila tidak menepati janji ini, kami siap menerima sanksi sesuai peraturan perundang-undangan yang berlaku.',
            'penandatangan' => 'Kepala UPTD Pendapatan Daerah Wilayah Kota Kupang',
            'berkas' => null,
        ],
    ];

    /**
     * The portal's Tentang UPTD page: introduction, position, identity
     * facts, duties and the head's welcome (Tentang UPTD).
     */
    public const string PROFILE = 'profil';

    /**
     * The Tentang UPTD document until it is first saved (D-029): the unit's
     * position, organisation and duties as the Renstra BPAD 2025–2029 (Pergub
     * NTT 46/2025) and the RPJMD NTT 2025–2029 (Perda NTT 6/2025) describe
     * them, in the portal's wording, with the head of the unit as the owner
     * named him. Without photos: the portal shows its own building photo
     * until one is uploaded (D-028).
     *
     * @var array{judul: string, lanjutan: string, pengantar: string, foto: null, keteranganFoto: string, kedudukan: array{judul: string, lanjutan: string, deskripsi: string}, identitas: list<array{id: string, label: string, nilai: string}>, tugasFungsi: list<array{id: string, judul: string, deskripsi: string, kataKunci: string}>, sambutan: array{kutipan: string, nama: string, jabatan: string, foto: null}}
     */
    public const array INITIAL_PROFILE = [
        'judul' => 'Melayani pajak daerah.',
        'lanjutan' => 'Dari jantung Kota Kupang.',
        'pengantar' => 'Kami satu dari 22 unit pelaksana teknis Badan Pendapatan dan Aset Daerah Provinsi NTT. Kami mengurus pajak kendaraan bermotor bagi warga Kota Kupang, bersama mitra Samsat dalam satu atap.',
        'foto' => null,
        'keteranganFoto' => 'Kantor Bersama Samsat Kota Kupang dengan dua mobil Samsat Keliling. Pajak, STNK, dan asuransi kecelakaan diurus di satu tempat.',
        'kedudukan' => [
            'judul' => 'Bagian dari BPAD NTT.',
            'lanjutan' => 'Dekat dengan warga kota.',
            'deskripsi' => 'UPTD adalah unit pelaksana teknis Badan Pendapatan dan Aset Daerah Provinsi Nusa Tenggara Timur, badan yang membantu Gubernur di bidang pendapatan dan aset daerah. Kedudukan, susunan organisasi, dan tugasnya diatur dalam Peraturan Gubernur NTT Nomor 96 Tahun 2023. UPTD melayani seluruh wilayah Kota Kupang.',
        ],
        'identitas' => [
            ['id' => 'identitas-1', 'label' => 'Nama unit', 'nilai' => 'UPTD Pendapatan Daerah Wilayah Kota Kupang'],
            ['id' => 'identitas-2', 'label' => 'Dikenal sebagai', 'nilai' => 'Samsat Kota Kupang'],
            ['id' => 'identitas-3', 'label' => 'Induk organisasi', 'nilai' => 'Badan Pendapatan dan Aset Daerah Provinsi NTT'],
            ['id' => 'identitas-4', 'label' => 'Dasar organisasi', 'nilai' => 'Peraturan Gubernur NTT Nomor 96 Tahun 2023'],
            ['id' => 'identitas-5', 'label' => 'Susunan organisasi', 'nilai' => 'Kepala UPTD, Subbagian Tata Usaha, Seksi Penetapan dan Penagihan, Seksi Verifikasi, dan kelompok jabatan fungsional'],
            ['id' => 'identitas-6', 'label' => 'Wilayah kerja', 'nilai' => 'Kota Kupang · 6 kecamatan, 51 kelurahan, 159 km²'],
            ['id' => 'identitas-7', 'label' => 'Mitra satu atap', 'nilai' => 'Polri (Satlantas) dan PT Jasa Raharja'],
            ['id' => 'identitas-8', 'label' => 'Pajak yang dikelola', 'nilai' => 'PKB dan BBNKB beserta opsennya untuk Pemerintah Kota, PBBKB, dan Pajak Air Permukaan'],
            ['id' => 'identitas-9', 'label' => 'Kanal pembayaran', 'nilai' => 'Kasir tunai, QRIS, dan bank mitra'],
        ],
        'tugasFungsi' => [
            ['id' => 'tugas-1', 'judul' => 'Merencanakan, mengendalikan, dan mengevaluasi pemungutan PKB, BBNKB, PBBKB, dan Pajak Air Permukaan di Kota Kupang.', 'deskripsi' => 'Termasuk lain-lain pendapatan asli daerah yang sah, untuk meningkatkan penerimaan daerah.', 'kataKunci' => 'Pungut'],
            ['id' => 'tugas-2', 'judul' => 'Penetapan dan penagihan pajak serta pendapatan lain-lain.', 'deskripsi' => 'Dijalankan Seksi Penetapan dan Penagihan, termasuk surat keterangan terdaftar kendaraan bermotor. Nilai pajak dihitung sistem, bukan oleh petugas loket.', 'kataKunci' => 'Tetapkan'],
            ['id' => 'tugas-3', 'judul' => 'Verifikasi data dan kebenaran penetapan pajak serta pendapatan lain-lain.', 'deskripsi' => 'Dijalankan Seksi Verifikasi, mulai dari pengumpulan data.', 'kataKunci' => 'Periksa'],
            ['id' => 'tugas-4', 'judul' => "Pelayanan Samsat: pengesahan STNK, perpanjangan 5\u{00A0}tahun, mutasi, dan balik nama.", 'deskripsi' => 'Layanan terpadu bersama Polri dan PT Jasa Raharja: pajak kendaraan dan asuransi kecelakaan di satu kantor.', 'kataKunci' => 'Layani'],
            ['id' => 'tugas-5', 'judul' => 'Menjangkau wajib pajak lewat Samsat Keliling, penagihan dari pintu ke pintu, dan pembayaran daring.', 'deskripsi' => 'Agar wajib pajak tidak selalu harus datang ke kantor.', 'kataKunci' => 'Jangkau'],
            ['id' => 'tugas-6', 'judul' => 'Mengawal target retribusi daerah dan memantau aset Pemerintah Provinsi di Kota Kupang.', 'deskripsi' => 'Selain pajak, UPTD ikut menjaga pendapatan dan aset daerah.', 'kataKunci' => 'Awasi'],
            ['id' => 'tugas-7', 'judul' => 'Ketatausahaan: kepegawaian, keuangan, perlengkapan, tata usaha umum, dan layanan administrasi.', 'deskripsi' => 'Dijalankan Subbagian Tata Usaha.', 'kataKunci' => 'Kelola'],
        ],
        'sambutan' => [
            'kutipan' => 'Kami ingin setiap warga datang sekali dan pulang dengan urusan selesai. PINTU adalah janji kami untuk informasi yang jelas, resmi, dan selalu terbaru.',
            'nama' => 'Remmy Christian Pah, S.STP, M.Si',
            'jabatan' => 'Kepala UPTD Pendapatan Daerah Wilayah Kota Kupang',
            'foto' => null,
        ],
    ];

    /**
     * The portal's identity and announcement bar (Pengaturan Situs). It
     * belongs to no dashboard module: every employee reads it, only
     * administrators save it (D-026).
     */
    public const string SITE = 'situs';

    /**
     * The Pengaturan Situs document until it is first saved: the identity
     * and announcement written in the portal's markup, which stay there and
     * are replaced only by a saved document (D-026).
     *
     * @var array{namaPortal: string, kepanjangan: string, namaInstansi: string, namaSingkat: string, induk: string, deskripsi: string, urlPortal: string, hakCipta: string, bilahPengumuman: array{aktif: bool, teks: string, tautanLabel: string, tautanUrl: string}}
     */
    public const array INITIAL_SITE = [
        'namaPortal' => 'PINTU',
        'kepanjangan' => 'Portal Informasi dan Pelayanan',
        'namaInstansi' => 'UPTD Pendapatan Daerah Wilayah Kota Kupang',
        'namaSingkat' => 'Samsat Kota Kupang',
        'induk' => 'Badan Pendapatan dan Aset Daerah Provinsi Nusa Tenggara Timur',
        'deskripsi' => 'Unit Pelaksana Teknis Daerah pada Badan Pendapatan dan Aset Daerah Provinsi Nusa Tenggara Timur. Kantor bersama Samsat Kota Kupang.',
        'urlPortal' => '../',
        'hakCipta' => '© 2026 UPTD Pendapatan Daerah Wilayah Kota Kupang · BPAD Provinsi NTT',
        'bilahPengumuman' => [
            'aktif' => true,
            'teks' => 'Program pemutihan denda PKB diperpanjang hingga 31 Desember 2026.',
            'tautanLabel' => 'Baca',
            'tautanUrl' => 'berita-detail.html',
        ],
    ];

    /**
     * The texts of the portal's homepage: opening, values, the services
     * heading, partners, channel map, integrity, fees and closing (Beranda).
     */
    public const string HOMEPAGE = 'beranda';

    /**
     * The Beranda document until it is first saved: the text written in the
     * homepage's markup, which stays there and is replaced only by a saved
     * document (D-023).
     *
     * @var array<string, mixed>
     */
    public const array INITIAL_HOMEPAGE = [
        'hero' => [
            'eyebrow' => 'UPTD Pendapatan Daerah Wilayah Kota Kupang · SAMSAT Kota Kupang',
            'judul' => 'Semua informasi Samsat.',
            // The title's matching word takes turns with the others for one round and stops on it (D-045)
            'kataBergilir' => ['Pajak', 'STNK', 'Mutasi', 'BBN', 'SAMSAT'],
            'lanjutan' => 'Lewat satu pintu.',
            'deskripsi' => 'PINTU adalah portal resmi informasi dan pelayanan pajak kendaraan di Kota Kupang. Cek persyaratan, jadwal, dan lokasi layanan sebelum Anda berangkat.',
            'tombolLabel' => 'Cek Persyaratan Layanan',
            'tombolUrl' => 'layanan.html',
            'catatan' => 'Gratis dan tanpa perantara. Setiap informasi diverifikasi petugas UPTD.',
        ],
        // The "Tonton alur pelayanan" button under the service board, hidden while its link is empty (D-040)
        'video' => ['url' => '', 'label' => 'Tonton alur pelayanan', 'durasi' => '', 'judul' => ''],
        'nilai' => [
            ['id' => 'nilai-1', 'judul' => 'Persyaratan jelas.', 'deskripsi' => 'Daftar berkas setiap layanan, lengkap dengan jumlah lembar fotokopi yang perlu dibawa.'],
            ['id' => 'nilai-2', 'judul' => 'Jadwal terbaru.', 'deskripsi' => 'Jam loket dan lokasi Samsat Keliling diperbarui petugas setiap hari kerja.'],
            ['id' => 'nilai-3', 'judul' => 'Tanpa perantara.', 'deskripsi' => 'Semua biaya mengikuti notis pajak resmi. Urus sendiri dengan mudah, tanpa calo.'],
        ],
        'layananUtama' => [
            'judul' => 'Dibuat untuk wajib pajak.',
            'lanjutan' => 'Dirancang agar cukup sekali datang.',
            'deskripsi' => 'Pilih layanan, siapkan berkas dari rumah, lalu datang ke loket yang tepat.',
        ],
        'mitra' => [
            ['id' => 'mitra-1', 'singkatan' => 'BP', 'nama' => 'BPAD Provinsi NTT', 'logo' => null],
            ['id' => 'mitra-2', 'singkatan' => 'PL', 'nama' => 'Polri · Satlantas', 'logo' => null],
            ['id' => 'mitra-3', 'singkatan' => 'JR', 'nama' => 'Jasa Raharja', 'logo' => null],
            ['id' => 'mitra-4', 'singkatan' => 'BN', 'nama' => 'Bank NTT', 'logo' => null],
            ['id' => 'mitra-5', 'singkatan' => 'QR', 'nama' => 'QRIS', 'logo' => null],
            ['id' => 'mitra-6', 'singkatan' => 'SG', 'nama' => 'SIGNAL', 'logo' => null],
        ],
        'kanal' => [
            ['id' => 'kanal-1', 'kelompok' => 'Kantor', 'item' => [
                ['teks' => 'Loket pajak tahunan', 'tautan' => ''],
                ['teks' => "Loket 5\u{00A0}tahunan", 'tautan' => ''],
                ['teks' => 'Loket mutasi & BBN', 'tautan' => ''],
                ['teks' => 'Cek fisik kendaraan', 'tautan' => ''],
            ]],
            ['id' => 'kanal-2', 'kelompok' => 'Keliling', 'item' => [
                ['teks' => 'Samsat Keliling', 'tautan' => ''],
                ['teks' => 'Layanan jemput bola', 'tautan' => ''],
            ]],
            ['id' => 'kanal-3', 'kelompok' => 'Digital', 'item' => [
                ['teks' => 'QRIS di kasir', 'tautan' => ''],
                ['teks' => 'Bank NTT', 'tautan' => ''],
                ['teks' => 'SIGNAL', 'tautan' => ''],
            ]],
            ['id' => 'kanal-4', 'kelompok' => 'Informasi', 'item' => [
                ['teks' => 'Portal PINTU', 'tautan' => ''],
                ['teks' => 'Media sosial', 'tautan' => ''],
                ['teks' => 'WhatsApp layanan', 'tautan' => ''],
            ]],
            ['id' => 'kanal-5', 'kelompok' => 'Pengaduan', 'item' => [
                ['teks' => 'SP4N-LAPOR!', 'tautan' => ''],
                ['teks' => 'Kotak saran', 'tautan' => ''],
                ['teks' => 'Ombudsman NTT', 'tautan' => ''],
            ]],
        ],
        'integritas' => [
            'judul' => 'Bayar sesuai notis.',
            'lanjutan' => 'Tidak lebih sepeser pun.',
            'deskripsi' => 'Seluruh pembayaran tercatat di sistem dan dapat dilakukan non-tunai. Petugas kami tidak pernah meminta uang atau data pribadi lewat pesan pribadi.',
        ],
        'biaya' => [
            'judul' => 'Biaya jelas.',
            'lanjutan' => 'Tanpa kejutan.',
            'deskripsi' => 'Pajak kendaraan dihitung sistem dan tercetak di notis. Layanan pendukung di kantor ini tidak dipungut biaya.',
            'catatanNotis' => 'Bayar sesuai notis. Tunai di kasir atau non-tunai lewat QRIS.',
            'wajibDibawa' => 'KTP asli dan STNK asli pemilik kendaraan.',
            'jaminan' => ['Bukti bayar resmi', 'Bisa non-tunai', 'Tercatat di sistem'],
        ],
        'penutup' => [
            'judul' => 'Map apa saja boleh.',
            'lanjutan' => 'Asal jangan lupa dibawa.',
            'deskripsi' => 'Hampir setiap layanan meminta satu map. Kami hanya mengingatkan.',
        ],
    ];

    protected $primaryKey = 'name';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    /**
     * The opening hours as the dashboard edits them. Until they are first
     * saved, every day is closed.
     *
     * @return array{hari: list<array{hari: int, buka: bool, mulai: string, selesai: string, catatan: string}>, batasBerkasMenit: int, catatan: string, pemberitahuan: string}
     */
    public static function openingHours(): array
    {
        return self::query()->find(self::OPENING_HOURS)?->value ?? [
            'hari' => array_map(
                fn (int $hari): array => ['hari' => $hari, 'buka' => false, 'mulai' => '', 'selesai' => '', 'catatan' => ''],
                [1, 2, 3, 4, 5, 6, 0],
            ),
            'batasBerkasMenit' => 60,
            'catatan' => '',
            'pemberitahuan' => '',
        ];
    }

    /**
     * The service board as the dashboard edits it. Until it is first saved it
     * is empty and "diperbarui" is null.
     *
     * @return array{nomorAntrean: string, layananAntrean: string, waktuTunggu: int, selisihKemarin: int, loket: list<array{id: string, kode: string, nama: string, status: string}>, catatan: string, diperbarui: ?string}
     */
    public static function serviceBoard(): array
    {
        return self::query()->find(self::SERVICE_BOARD)?->value ?? [
            'nomorAntrean' => '',
            'layananAntrean' => '',
            'waktuTunggu' => 0,
            'selisihKemarin' => 0,
            'loket' => [],
            'catatan' => '',
            'diperbarui' => null,
        ];
    }

    /**
     * The contact document as the dashboard edits it.
     *
     * @return array{namaKantor: string, alamat: string, kota: string, provinsi: string, telepon: string, whatsapp: string, whatsappCatatan: string, email: string, mediaSosial: list<array{id: string, platform: string, akun: string, url: string}>, mapsUrl: string, catatanLokasi: string, janjiBalasan: string, kanalPengaduan: list<array{id: string, nama: string, deskripsi: string, tautanLabel: string, tautanUrl: string}>}
     */
    public static function contact(): array
    {
        return self::query()->find(self::CONTACT)?->value ?? self::UNSAVED_CONTACT;
    }

    /**
     * The Visi & Misi document as the dashboard edits it.
     *
     * @return array{sumberVisi: string, visi: string, misi: list<array{id: string, teks: string, kataKunci: string}>, kontribusi: list<array{id: string, misi: string, judul: string, deskripsi: string}>, nilai: list<array{id: string, huruf: string, judul: string, deskripsi: string}>, maklumat: array{teks: string, penandatangan: string, berkas: ?array{nama: string, ukuran: int, tipe: string, url: string}}}
     */
    public static function visionMission(): array
    {
        return self::query()->find(self::VISION_MISSION)?->value ?? self::INITIAL_VISION_MISSION;
    }

    /**
     * The Tentang UPTD document as the dashboard edits it.
     *
     * @return array{judul: string, lanjutan: string, pengantar: string, foto: ?string, keteranganFoto: string, kedudukan: array{judul: string, lanjutan: string, deskripsi: string}, identitas: list<array{id: string, label: string, nilai: string}>, tugasFungsi: list<array{id: string, judul: string, deskripsi: string, kataKunci: string}>, sambutan: array{kutipan: string, nama: string, jabatan: string, foto: ?string}}
     */
    public static function profile(): array
    {
        return self::query()->find(self::PROFILE)?->value ?? self::INITIAL_PROFILE;
    }

    /**
     * The Pengaturan Situs document as the dashboard edits it.
     *
     * @return array{namaPortal: string, kepanjangan: string, namaInstansi: string, namaSingkat: string, induk: string, deskripsi: string, urlPortal: string, hakCipta: string, bilahPengumuman: array{aktif: bool, teks: string, tautanLabel: string, tautanUrl: string}}
     */
    public static function site(): array
    {
        return self::query()->find(self::SITE)?->value ?? self::INITIAL_SITE;
    }

    /**
     * The Beranda document as the dashboard edits it.
     *
     * @return array<string, mixed>
     */
    public static function homepage(): array
    {
        return self::upgradeHomepage(self::query()->find(self::HOMEPAGE)?->value ?? self::INITIAL_HOMEPAGE);
    }

    /**
     * A Beranda document in its current shape, whenever it was saved: every
     * partner has a logo address or null (D-037), every channel is a text
     * with a link, empty when it has none (D-038), and there is a video, with
     * no link until one is saved (D-040). Channels saved as plain text get no
     * link. An opening saved before its rotating words (D-045) gets the
     * initial ones when its title holds one of them, and none otherwise.
     *
     * @param  array<string, mixed>  $homepage
     * @return array<string, mixed>
     */
    public static function upgradeHomepage(array $homepage): array
    {
        $words = self::INITIAL_HOMEPAGE['hero']['kataBergilir'];
        $homepage['hero']['kataBergilir'] ??= self::rotatingWordIn($homepage['hero']['judul'], $words) === null ? [] : $words;
        $homepage['mitra'] = array_map(fn (array $partner): array => [...$partner, 'logo' => $partner['logo'] ?? null], $homepage['mitra']);
        $homepage['kanal'] = array_map(fn (array $group): array => [
            ...$group,
            'item' => array_map(fn (string|array $channel): array => is_string($channel) ? ['teks' => $channel, 'tautan' => ''] : $channel, $group['item']),
        ], $homepage['kanal']);
        $homepage['video'] = [...self::INITIAL_HOMEPAGE['video'], ...($homepage['video'] ?? [])];

        return $homepage;
    }

    /**
     * The first of the rotating words that the title holds as a whole word,
     * in any case; the portal's rotation starts and stops there (D-045).
     * resources/js/portal/kata-bergilir.ts matches the same way.
     *
     * @param  list<string>  $words
     */
    public static function rotatingWordIn(string $title, array $words): ?string
    {
        foreach ($words as $word) {
            if (preg_match('/(?<![\p{L}\p{N}])'.preg_quote($word, '/').'(?![\p{L}\p{N}])/iu', $title) === 1) {
                return $word;
            }
        }

        return null;
    }

    /**
     * The statistics as the dashboard edits them. Until they are first saved,
     * the four categories and their tiles are named but every figure is zero
     * and the status is "ilustrasi", so nothing is published.
     *
     * @return array{status: string, catatan: string, tahun: string, set: list<array{kunci: string, label: string, sub: string, judulGrafik: string, satuan: string, tiles: list<array{id: string, label: string, nilai: int|float, satuan: string, perubahan: int|float, naikBaik: bool}>, seri: list<int|float>}>}
     */
    public static function statistics(): array
    {
        return self::query()->find(self::STATISTICS)?->value ?? [
            'status' => 'ilustrasi',
            'catatan' => '',
            'tahun' => now('Asia/Makassar')->format('Y'),
            'set' => array_map(fn (array $category): array => [
                'kunci' => $category[0],
                'label' => $category[1],
                'sub' => $category[2],
                'judulGrafik' => $category[3],
                'satuan' => $category[4],
                'tiles' => array_map(fn (array $tile, int $index): array => [
                    'id' => "{$category[0]}-".($index + 1),
                    'label' => $tile[0],
                    'nilai' => 0,
                    'satuan' => $tile[1],
                    'perubahan' => 0,
                    'naikBaik' => $tile[2],
                ], $category[5], array_keys($category[5])),
                'seri' => array_fill(0, 12, 0),
            ], [
                ['transaksi', 'Transaksi pelayanan', 'Transaksi pelayanan · UPTD Kota Kupang', 'Transaksi per bulan', 'transaksi', [
                    ['Total transaksi', '', true], ['Pajak tahunan', '', true], ['Perpanjangan 5 tahun', '', true], ['Mutasi & BBN', '', true],
                ]],
                ['penerimaan', 'Penerimaan pajak', 'Penerimaan pajak daerah · UPTD Kota Kupang', 'Penerimaan PKB per bulan', 'juta rupiah', [
                    ['Penerimaan PKB', 'M', true], ['BBNKB', 'M', true], ['Opsen PKB (Pemkot)', 'M', true], ['Capaian target', '%', true],
                ]],
                ['keliling', 'Samsat Keliling', 'Samsat Keliling · 6 kecamatan, 51 kelurahan', 'Transaksi keliling per bulan', 'transaksi', [
                    ['Titik layanan', '', true], ['Transaksi keliling', '', true], ['Rata-rata per titik', '', true], ['Kelurahan terjangkau', '/51', true],
                ]],
                ['kepuasan', 'Kepuasan masyarakat', 'Survei Kepuasan Masyarakat (IKM)', 'Waktu tunggu rata-rata per bulan', 'menit', [
                    ['Indeks kepuasan', '', true], ['Waktu tunggu rata-rata', 'mnt', false], ['Pengaduan selesai', '%', true], ['Responden survei', '', true],
                ]],
            ]),
        ];
    }
}
