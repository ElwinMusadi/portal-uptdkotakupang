/*
 * Data awal dashboard.
 *
 * Isinya diambil dari halaman portal PINTU yang sudah ada (beranda, layanan,
 * jadwal, profil, dst.), termasuk penanda "contoh" yang memang masih tertulis
 * di portal. Waktu riwayat & pesan dibuat relatif terhadap saat pertama kali
 * dashboard dibuka agar grafik dan status pemutakhiran tetap bermakna.
 */
import { newId } from "@/lib/id"
import type {
  Absensi,
  Aktivitas,
  Beralur,
  Berita,
  Dokumen,
  Dokumentasi,
  Faq,
  Halaman,
  Hari,
  JadwalKeliling,
  JenisPesan,
  Layanan,
  Pemutakhiran,
  PenyesuaianJadwal,
  Pengaturan,
  Pesan,
  Riwayat,
  Snapshot,
  StatusAlur,
  StatusPesan,
  SumberPesan,
  UnitKerja,
} from "./types"

export const VERSI_DATA = 1

/** Waktu ISO pada jam tertentu (WITA, UTC+8) beberapa hari sebelum `sekarang` */
function waktu(sekarang: Date, hariLalu: number, jam = 9, menit = 0): string {
  const acuan = new Date(sekarang.getTime() - hariLalu * 86400000)
  const wita = new Date(acuan.getTime() + 8 * 3600000)
  const d = new Date(
    Date.UTC(wita.getUTCFullYear(), wita.getUTCMonth(), wita.getUTCDate(), jam - 8, menit)
  )
  // jangan sampai waktu contoh melampaui "sekarang"
  return (d > sekarang ? new Date(sekarang.getTime() - 5 * 60000) : d).toISOString()
}

/** Pembangkit acak deterministik agar data contoh selalu sama */
function acak(benih: number) {
  let s = benih >>> 0
  return () => {
    s = (s + 0x6d2b79f5) >>> 0
    let t = s
    t = Math.imul(t ^ (t >>> 15), t | 1)
    t ^= t + Math.imul(t ^ (t >>> 7), t | 61)
    return ((t ^ (t >>> 14)) >>> 0) / 4294967296
  }
}

const OPERATOR = "Operator PINTU"
const KASUBBAG = "Kepala Sub Bagian Tata Usaha"
const KEPALA = "Kepala UPTD"

interface OpsiAlur {
  status: StatusAlur
  penyedia: string
  pemeriksa?: string
  /** hari sejak bahan pertama kali dibuat */
  mulai: number
  catatanRevisi?: string
}

/** Bangun kolom alur publikasi beserta riwayat yang konsisten dengan statusnya */
function alur(sekarang: Date, o: OpsiAlur): Beralur & { dibuat: string; diperbarui: string } {
  const pemeriksa = o.pemeriksa ?? KASUBBAG
  const langkah: Riwayat[] = [
    { waktu: waktu(sekarang, o.mulai, 8, 30), oleh: o.penyedia, aksi: "menyiapkan draf", ke: "draf" },
  ]
  const urutan: StatusAlur[] =
    o.status === "dikembalikan"
      ? ["diperiksa", "dikembalikan"]
      : o.status === "arsip"
        ? ["diperiksa", "persetujuan", "terbit", "arsip"]
        : (["diperiksa", "persetujuan", "terbit"] as StatusAlur[]).slice(
            0,
            ["draf", "diperiksa", "persetujuan", "terbit"].indexOf(o.status)
          )
  const kerja: Record<string, [string, string]> = {
    diperiksa: [o.penyedia, "mengajukan pemeriksaan"],
    persetujuan: [pemeriksa, "meneruskan ke penyetuju"],
    terbit: [KEPALA, "menyetujui dan menerbitkan"],
    dikembalikan: [pemeriksa, "mengembalikan"],
    arsip: [OPERATOR, "menarik dari portal"],
  }
  urutan.forEach((ke, i) => {
    // langkah berikutnya tersebar merata antara hari pembuatan dan hari ini
    const hari = Math.max(0, Math.round(o.mulai * (1 - (i + 1) / (urutan.length + 1))))
    const [oleh, aksi] = kerja[ke]
    langkah.push({
      waktu: waktu(sekarang, hari, 10 + i, 15 * i),
      oleh,
      aksi,
      ke,
      catatan: ke === "dikembalikan" ? o.catatanRevisi : undefined,
    })
  })
  const riwayat = langkah.reverse()
  const terbit = riwayat.find((r) => r.ke === "terbit")
  return {
    status: o.status,
    penyedia: o.penyedia,
    pemeriksa,
    catatanRevisi: o.catatanRevisi ?? "",
    terbitPada: o.status === "terbit" || o.status === "arsip" ? (terbit?.waktu ?? null) : null,
    riwayat,
    dibuat: riwayat[riwayat.length - 1].waktu,
    diperbarui: riwayat[0].waktu,
  }
}

function berita(sekarang: Date): Berita[] {
  const data: Array<
    Omit<Berita, keyof Beralur | "id" | "dibuat" | "diperbarui" | "urutan"> & { alur: OpsiAlur }
  > = [
    {
      judul: "PINTU resmi diluncurkan. Informasi Samsat kini di satu portal.",
      slug: "pintu-resmi-diluncurkan",
      kategori: "berita",
      ringkasan:
        "Persyaratan, jadwal, dan lokasi Samsat Keliling kini dapat dicek dari rumah. Seluruh isi diverifikasi petugas sebelum terbit.",
      isi: `<p>Selama ini, informasi persyaratan layanan Samsat Kota Kupang ditempel pada kertas di meja dan kaca loket. Kertas mudah rusak dan harus diganti terus-menerus. Warga juga perlu bertanya langsung kepada petugas untuk hal yang sebenarnya bisa dibaca sebelum datang.</p><p>PINTU, singkatan dari Portal Informasi dan Pelayanan, hadir untuk menjawab masalah itu. Semua informasi resmi UPTD Pendapatan Daerah Wilayah Kota Kupang kini terhimpun di satu alamat.</p><h2>Apa saja yang tersedia.</h2><ul><li><p>Persyaratan lima layanan utama, lengkap dengan jumlah lembar fotokopi.</p></li><li><p>Jam loket dengan status buka atau tutup secara langsung.</p></li><li><p>Jadwal dan lokasi Samsat Keliling setiap minggu.</p></li><li><p>Berita, pengumuman, dokumentasi kegiatan, serta dokumen regulasi.</p></li><li><p>Formulir masukan dan kanal pengaduan.</p></li></ul><blockquote><p>“Informasi yang sama, untuk semua orang, kapan saja. Itu inti dari PINTU.”</p></blockquote><h2>Bagaimana isinya dijaga.</h2><p>Setiap informasi melewati empat peran sebelum terbit: penyedia data dari unit terkait, pemeriksa, atasan langsung sebagai penyetuju, dan operator sebagai pengelola publikasi. Jadwal Samsat Keliling diperbarui mingguan, sedangkan persyaratan diperbarui setiap ada perubahan regulasi.</p><h2>Sampaikan masukan Anda.</h2><p>Ada informasi yang kurang jelas atau belum tercantum? Kirimkan lewat <a href="kontak.html#masukan">formulir masukan</a>. Masukan pengguna menjadi bahan evaluasi dan pemutakhiran portal.</p>`,
      sampul: null,
      keteranganSampul: "Petugas loket mencoba portal PINTU bersama wajib pajak.",
      kreditFoto: "Sub Bagian Tata Usaha",
      tanggal: "2026-10-27",
      sorotan: true,
      pengumumanBeranda: false,
      alur: { status: "terbit", penyedia: "Pengelola publikasi", mulai: 6 },
    },
    {
      judul: "Program pemutihan denda PKB diperpanjang hingga 31 Desember 2026.",
      slug: "pemutihan-denda-pkb-diperpanjang",
      kategori: "pengumuman",
      ringkasan:
        "Berlaku untuk keterlambatan pajak tahunan. Bawa notis terakhir ke loket pajak tahunan.",
      isi: `<p>Program pemutihan denda Pajak Kendaraan Bermotor (PKB) diperpanjang hingga 31 Desember 2026. Program berlaku untuk keterlambatan pembayaran pajak tahunan.</p><h2>Yang perlu dibawa.</h2><ul><li><p>Notis pajak terakhir dan STNK asli.</p></li><li><p>KTP asli pemilik kendaraan.</p></li></ul><p>Pembayaran tetap mengikuti nilai pokok pada notis. Gunakan simulasi pajak di portal untuk memperkirakan total pembayaran sebelum datang.</p>`,
      sampul: null,
      keteranganSampul: "",
      kreditFoto: "",
      tanggal: "2026-10-27",
      sorotan: false,
      pengumumanBeranda: true,
      alur: { status: "persetujuan", penyedia: "Seksi Penagihan & Pelaporan", mulai: 3 },
    },
    {
      judul: "Samsat Keliling menjangkau 31 kelurahan tahun ini.",
      slug: "samsat-keliling-31-kelurahan",
      kategori: "keliling",
      ringkasan: "Layanan jemput bola menjadi pilihan warga yang sulit datang ke kantor di jam kerja.",
      isi: `<p>Samsat Keliling hadir di kantor kelurahan sesuai jadwal mingguan. Warga cukup membawa STNK, notis pajak, dan KTP asli untuk membayar pajak tahunan tanpa harus ke kantor.</p><p>Jadwal minggu berikutnya diumumkan setiap Jumat di halaman jadwal portal PINTU.</p>`,
      sampul: null,
      keteranganSampul: "Samsat Keliling · Oesapa",
      kreditFoto: "Seksi Penagihan & Pelaporan",
      tanggal: "2026-10-23",
      sorotan: false,
      pengumumanBeranda: false,
      alur: { status: "draf", penyedia: "Seksi Penagihan & Pelaporan", mulai: 1 },
    },
    {
      judul: "Jadwal Samsat Keliling pekan depan telah terbit.",
      slug: "jadwal-samsat-keliling-pekan-depan",
      kategori: "pengumuman",
      ringkasan: "Cek lokasi dan jam layanan Samsat Keliling di kelurahan Anda.",
      isi: `<p>Jadwal Samsat Keliling untuk pekan depan telah tersedia di halaman jadwal. Samsat Keliling hanya melayani pembayaran pajak tahunan.</p>`,
      sampul: null,
      keteranganSampul: "",
      kreditFoto: "",
      tanggal: "2026-10-23",
      sorotan: false,
      pengumumanBeranda: false,
      alur: { status: "diperiksa", penyedia: "Seksi Penagihan & Pelaporan", mulai: 2 },
    },
    {
      judul: "Lima berkas yang paling sering terlupa saat perpanjangan STNK.",
      slug: "lima-berkas-sering-terlupa",
      kategori: "edukasi",
      ringkasan: "Mulai dari BPKB halaman 2–3 sampai map. Cek daftar ini sebelum berangkat.",
      isi: `<p>Perpanjangan STNK 5 tahun memerlukan cek fisik kendaraan dan beberapa fotokopi. Berikut berkas yang paling sering terlupa:</p><ol><li><p>Fotokopi BPKB halaman 2–3.</p></li><li><p>Fotokopi KTP pemilik yang sesuai alamat di STNK dan BPKB.</p></li><li><p>Notis pajak asli.</p></li><li><p>Surat kuasa bermeterai bila diwakilkan.</p></li><li><p>Map.</p></li></ol><p>Daftar lengkap tersedia di halaman persyaratan layanan.</p>`,
      sampul: null,
      keteranganSampul: "",
      kreditFoto: "",
      tanggal: "2026-10-20",
      sorotan: false,
      pengumumanBeranda: false,
      alur: { status: "terbit", penyedia: "Seksi Pendataan & Penetapan", mulai: 9 },
    },
    {
      judul: "Pembayaran QRIS kini tersedia di semua kasir.",
      slug: "qris-di-semua-kasir",
      kategori: "berita",
      ringkasan: "Wajib pajak cukup memindai kode dari aplikasi bank atau dompet digital.",
      isi: `<p>Seluruh kasir di Kantor Bersama Samsat Kota Kupang kini menerima pembayaran QRIS. Pembayaran non-tunai tercatat di sistem dan bukti transaksinya dapat disimpan sampai STNK selesai disahkan.</p>`,
      sampul: null,
      keteranganSampul: "Kasir non-tunai",
      kreditFoto: "Sub Bagian Tata Usaha",
      tanggal: "2026-10-16",
      sorotan: false,
      pengumumanBeranda: false,
      alur: { status: "terbit", penyedia: "Seksi Penagihan & Pelaporan", mulai: 14 },
    },
    {
      judul: "Penyesuaian jam layanan Jumat selama kegiatan apel bersama.",
      slug: "penyesuaian-jam-layanan-jumat",
      kategori: "pengumuman",
      ringkasan: "Loket tetap buka pukul 08.00 dengan penerimaan berkas hingga 10.30.",
      isi: `<p>Selama kegiatan apel bersama, loket tetap buka pukul 08.00 WITA. Penerimaan berkas pada hari Jumat dibatasi hingga pukul 10.30 WITA.</p>`,
      sampul: null,
      keteranganSampul: "",
      kreditFoto: "",
      tanggal: "2026-10-12",
      sorotan: false,
      pengumumanBeranda: false,
      alur: { status: "terbit", penyedia: "Sub Bagian Tata Usaha", mulai: 18 },
    },
    {
      judul: "Cara membaca notis pajak kendaraan Anda.",
      slug: "cara-membaca-notis-pajak",
      kategori: "edukasi",
      ringkasan: "Pokok PKB, opsen, dan SWDKLLJ dijelaskan baris demi baris.",
      isi: `<p>Notis pajak memuat beberapa komponen. Pokok PKB adalah pajak provinsi, opsen PKB adalah bagian untuk pemerintah kota, dan SWDKLLJ adalah sumbangan wajib dana kecelakaan lalu lintas jalan yang dikelola Jasa Raharja.</p><p>Nilai yang mengikat adalah nilai yang tercetak pada notis resmi.</p>`,
      sampul: null,
      keteranganSampul: "",
      kreditFoto: "",
      tanggal: "2026-10-08",
      sorotan: false,
      pengumumanBeranda: false,
      alur: { status: "terbit", penyedia: "Seksi Pendataan & Penetapan", mulai: 22 },
    },
    {
      judul: "Sosialisasi pajak kendaraan bersama ketua RT di Kecamatan Alak.",
      slug: "sosialisasi-ketua-rt-alak",
      kategori: "berita",
      ringkasan: "Warga bertanya langsung soal mutasi, balik nama, dan program pemutihan.",
      isi: `<p>UPTD Pendapatan Daerah Wilayah Kota Kupang mengadakan sosialisasi pajak kendaraan bersama para ketua RT di Kecamatan Alak. Warga bertanya langsung soal mutasi, balik nama, dan program pemutihan denda.</p>`,
      sampul: null,
      keteranganSampul: "Sosialisasi",
      kreditFoto: "Seksi Penagihan & Pelaporan",
      tanggal: "2026-10-02",
      sorotan: false,
      pengumumanBeranda: false,
      alur: { status: "terbit", penyedia: "Seksi Penagihan & Pelaporan", mulai: 30 },
    },
    {
      judul: "Waspada penipuan yang mengatasnamakan petugas Samsat.",
      slug: "waspada-penipuan",
      kategori: "pengumuman",
      ringkasan:
        "Petugas tidak pernah meminta uang atau data pribadi lewat pesan pribadi.",
      isi: `<p>Petugas kami tidak pernah meminta uang, kata sandi, PIN, atau kode OTP lewat pesan pribadi. Seluruh pembayaran dilakukan di kasir resmi atau kanal non-tunai yang tercatat di sistem.</p><p>Laporkan bila Anda menerima pesan mencurigakan melalui kanal pengaduan resmi.</p>`,
      sampul: null,
      keteranganSampul: "",
      kreditFoto: "",
      tanggal: "2026-10-01",
      sorotan: false,
      pengumumanBeranda: false,
      alur: { status: "terbit", penyedia: "Sub Bagian Tata Usaha", mulai: 34 },
    },
  ]
  return data.map(({ alur: o, ...b }, i) => ({ id: newId(), urutan: i, ...b, ...alur(sekarang, o) }))
}

function dokumentasi(sekarang: Date): Dokumentasi[] {
  const foto = (...keterangan: string[]) =>
    keterangan.map((k) => ({ id: newId(), src: null, keterangan: k }))
  const data: Array<
    Omit<Dokumentasi, keyof Beralur | "id" | "dibuat" | "diperbarui" | "urutan"> & { alur: OpsiAlur }
  > = [
    {
      judul: "Samsat Keliling di Kelurahan Oesapa.",
      kategori: "keliling",
      lokasi: "Kec. Kelapa Lima",
      tanggal: "2026-10-21",
      deskripsi: "Pelayanan pajak tahunan di Kantor Kelurahan Oesapa.",
      foto: foto("Samsat Keliling di Kelurahan Oesapa."),
      alur: { status: "terbit", penyedia: "Seksi Penagihan & Pelaporan", mulai: 5 },
    },
    {
      judul: "Operasi gabungan pemeriksaan pajak kendaraan.",
      kategori: "operasi",
      lokasi: "Jl. El Tari",
      tanggal: "2026-10-15",
      deskripsi: "Operasi gabungan bersama Satlantas dan Jasa Raharja.",
      foto: foto(
        "Operasi gabungan pemeriksaan pajak kendaraan.",
        "Pemeriksaan fisik kendaraan saat operasi gabungan · Jl. Frans Seda",
        "Petugas mencocokkan data notis di lapangan."
      ),
      alur: { status: "draf", penyedia: "Seksi Penagihan & Pelaporan", mulai: 1 },
    },
    {
      judul: "Antrean Samsat Keliling di Kelurahan Sikumana.",
      kategori: "keliling",
      lokasi: "Kec. Maulafa",
      tanggal: "2026-10-14",
      deskripsi: "",
      foto: foto("Antrean Samsat Keliling di Kelurahan Sikumana."),
      alur: { status: "terbit", penyedia: "Seksi Penagihan & Pelaporan", mulai: 12 },
    },
    {
      judul: "Pelayanan Samsat Keliling di Kelurahan Penfui.",
      kategori: "keliling",
      lokasi: "Kec. Maulafa",
      tanggal: "2026-10-08",
      deskripsi: "",
      foto: foto("Pelayanan Samsat Keliling di Kelurahan Penfui."),
      alur: { status: "terbit", penyedia: "Seksi Penagihan & Pelaporan", mulai: 16 },
    },
    {
      judul: "Konsultasi alur pengelolaan informasi dengan atasan langsung.",
      kategori: "internal",
      lokasi: "Ruang Kepala UPTD",
      tanggal: "2026-10-06",
      deskripsi: "Pembahasan alur pengumpulan, verifikasi, persetujuan, dan publikasi informasi.",
      foto: foto("Konsultasi alur pengelolaan informasi dengan atasan langsung."),
      alur: { status: "terbit", penyedia: "Sub Bagian Tata Usaha", mulai: 20 },
    },
    {
      judul: "Sosialisasi pajak kendaraan bersama ketua RT.",
      kategori: "sosialisasi",
      lokasi: "Kec. Alak",
      tanggal: "2026-10-02",
      deskripsi: "",
      foto: foto("Sosialisasi pajak kendaraan bersama ketua RT."),
      alur: { status: "terbit", penyedia: "Seksi Penagihan & Pelaporan", mulai: 28 },
    },
    {
      judul: "Gedung Kantor Bersama Samsat Kota Kupang.",
      kategori: "internal",
      lokasi: "Dokumentasi kantor",
      tanggal: "2026-10-01",
      deskripsi: "",
      foto: foto("Gedung Kantor Bersama Samsat Kota Kupang."),
      alur: { status: "terbit", penyedia: "Sub Bagian Tata Usaha", mulai: 32 },
    },
    {
      judul: "Sosialisasi alur pengelolaan konten kepada operator.",
      kategori: "sosialisasi",
      lokasi: "Aula UPTD",
      tanggal: "2026-11-04",
      deskripsi: "Rencana kegiatan. Foto ditambahkan setelah kegiatan berlangsung.",
      foto: [],
      alur: { status: "diperiksa", penyedia: "Sub Bagian Tata Usaha", mulai: 2 },
    },
    {
      judul: "Uji coba portal PINTU bersama petugas loket.",
      kategori: "internal",
      lokasi: "Ruang rapat UPTD",
      tanggal: "2026-11-18",
      deskripsi: "Rencana kegiatan uji fungsi, tampilan, keterbacaan, dan responsivitas portal.",
      foto: [],
      alur: { status: "draf", penyedia: "Pengelola publikasi", mulai: 0 },
    },
  ]
  return data.map(({ alur: o, ...d }, i) => ({ id: newId(), urutan: i, ...d, ...alur(sekarang, o) }))
}

function dokumen(sekarang: Date): Dokumen[] {
  const data: Array<
    Omit<Dokumen, keyof Beralur | "id" | "dibuat" | "diperbarui" | "urutan"> & { alur: OpsiAlur }
  > = [
    {
      judul: "Undang-Undang Nomor 1 Tahun 2022",
      keterangan: "Hubungan Keuangan antara Pemerintah Pusat dan Pemerintahan Daerah",
      kategori: "regulasi",
      format: "PDF",
      nomor: "1",
      tahun: "2022",
      berkas: null,
      tautan: "",
      alur: { status: "terbit", penyedia: "Sub Bagian Tata Usaha", mulai: 40 },
    },
    {
      judul: "Peraturan Daerah Provinsi NTT tentang Pajak Daerah dan Retribusi Daerah",
      keterangan: "Tarif PKB, BBNKB, dan opsen",
      kategori: "regulasi",
      format: "PDF",
      nomor: "",
      tahun: "",
      berkas: null,
      tautan: "",
      alur: { status: "terbit", penyedia: "Sub Bagian Tata Usaha", mulai: 40 },
    },
    {
      judul: "Peraturan Gubernur NTT tentang Organisasi dan Tata Kerja UPTD",
      keterangan: "Kedudukan, susunan organisasi, dan uraian tugas",
      kategori: "regulasi",
      format: "PDF",
      nomor: "",
      tahun: "",
      berkas: null,
      tautan: "",
      alur: { status: "terbit", penyedia: "Sub Bagian Tata Usaha", mulai: 40 },
    },
    {
      judul: "Standar Pelayanan UPTD Pendapatan Daerah Wilayah Kota Kupang",
      keterangan: "Persyaratan, prosedur, waktu, biaya, produk, dan pengaduan",
      kategori: "standar",
      format: "PDF",
      nomor: "",
      tahun: "2026",
      berkas: null,
      tautan: "",
      alur: { status: "terbit", penyedia: "Sub Bagian Tata Usaha", mulai: 35 },
    },
    {
      judul: "Maklumat Pelayanan",
      keterangan: "Janji layanan yang ditandatangani Kepala UPTD",
      kategori: "standar",
      format: "PDF",
      nomor: "",
      tahun: "2026",
      berkas: null,
      tautan: "",
      alur: { status: "terbit", penyedia: "Sub Bagian Tata Usaha", mulai: 35 },
    },
    {
      judul: "SOP Pengelolaan dan Publikasi Informasi PINTU",
      keterangan: "Alur pengumpulan, verifikasi, persetujuan, publikasi, dan pemutakhiran",
      kategori: "standar",
      format: "PDF",
      nomor: "",
      tahun: "2026",
      berkas: null,
      tautan: "",
      alur: { status: "terbit", penyedia: "Pengelola publikasi", mulai: 15 },
    },
    {
      judul: "Formulir surat kuasa",
      keterangan: "Untuk perpanjangan 5 tahun dan duplikat STNK · meterai Rp10.000",
      kategori: "formulir",
      format: "DOCX",
      nomor: "",
      tahun: "",
      berkas: null,
      tautan: "",
      alur: { status: "terbit", penyedia: "Seksi Pendataan & Penetapan", mulai: 30 },
    },
    {
      judul: "Formulir surat kuasa versi 2026",
      keterangan: "Pembaruan format surat kuasa · meterai Rp10.000",
      kategori: "formulir",
      format: "DOCX",
      nomor: "",
      tahun: "2026",
      berkas: null,
      tautan: "",
      alur: { status: "persetujuan", penyedia: "Seksi Pendataan & Penetapan", mulai: 2 },
    },
    {
      judul: "Formulir surat pernyataan STNK hilang/rusak",
      keterangan: "Ditandatangani di atas meterai Rp10.000",
      kategori: "formulir",
      format: "DOCX",
      nomor: "",
      tahun: "",
      berkas: null,
      tautan: "",
      alur: { status: "terbit", penyedia: "Seksi Pendataan & Penetapan", mulai: 30 },
    },
    {
      judul: "Laporan Survei Kepuasan Masyarakat Semester I 2026",
      keterangan: "Indeks kepuasan dan tindak lanjut",
      kategori: "laporan",
      format: "PDF",
      nomor: "",
      tahun: "2026",
      berkas: null,
      tautan: "",
      alur: { status: "terbit", penyedia: "Sub Bagian Tata Usaha", mulai: 25 },
    },
  ]
  return data.map(({ alur: o, ...d }, i) => ({ id: newId(), urutan: i, ...d, ...alur(sekarang, o) }))
}

function layanan(sekarang: Date): Layanan[] {
  const s = (dokumen: string, jumlah: string) => ({ id: newId(), dokumen, jumlah })
  const data: Array<
    Omit<Layanan, keyof Beralur | "id" | "dibuat" | "diperbarui" | "urutan"> & { alur: OpsiAlur }
  > = [
    {
      nama: "Pajak tahunan",
      slug: "pajak-tahunan",
      ringkasan: "Pembayaran PKB dan pengesahan STNK setiap tahun.",
      loket: "Loket 1",
      jenis: "layanan",
      cekFisik: false,
      nonTunai: true,
      diwakilkan: true,
      label: [],
      persyaratan: [
        s("Fotokopi STNK dan notis pajak (lampirkan asli)", "2 lembar"),
        s("Fotokopi BPKB halaman 2–3", "2 lembar"),
        s("Fotokopi KTP pemilik (sesuai alamat di STNK dan BPKB)", "2 lembar"),
        s("Map", "1 buah"),
      ],
      catatan: "",
      unggulan: true,
      alur: { status: "terbit", penyedia: "Seksi Pendataan & Penetapan", mulai: 20 },
    },
    {
      nama: "Perpanjangan STNK 5 tahun",
      slug: "perpanjangan-5-tahun",
      ringkasan: "Penggantian STNK dan pelat nomor setiap lima tahun.",
      loket: "Loket 2",
      jenis: "layanan",
      cekFisik: true,
      nonTunai: true,
      diwakilkan: true,
      label: ["Kuasa: meterai Rp10.000"],
      persyaratan: [
        s("Fotokopi STNK dan notis pajak (lampirkan asli)", "2 lembar"),
        s("Fotokopi BPKB halaman 2–3", "2 lembar"),
        s("Fotokopi KTP pemilik (sesuai alamat di STNK dan BPKB)", "2 lembar"),
        s("Map", "1 buah"),
        s("Hadirkan kendaraan untuk cek fisik", "—"),
      ],
      catatan:
        "Jika dikuasakan, sertakan surat kuasa bertanda tangan di atas meterai Rp10.000 (1 lembar).",
      unggulan: true,
      alur: { status: "terbit", penyedia: "Seksi Pendataan & Penetapan", mulai: 20 },
    },
    {
      nama: "Mutasi keluar, pemilik tetap",
      slug: "mutasi-pemilik-tetap",
      ringkasan: "Pindah alamat kendaraan ke luar Kota Kupang tanpa ganti pemilik.",
      loket: "Loket 3",
      jenis: "layanan",
      cekFisik: true,
      nonTunai: false,
      diwakilkan: false,
      label: [],
      persyaratan: [
        s("Fotokopi KTP alamat tujuan yang baru", "2 lembar"),
        s("Fotokopi STNK dan BPKB (lampirkan yang asli)", "2 lembar"),
        s("Bawa kendaraan untuk cek fisik", "—"),
        s("Map", "1 buah"),
      ],
      catatan: "",
      unggulan: true,
      alur: { status: "terbit", penyedia: "Seksi Pendataan & Penetapan", mulai: 20 },
    },
    {
      nama: "Mutasi keluar, ganti pemilik",
      slug: "mutasi-ganti-pemilik",
      ringkasan: "Pindah alamat ke luar daerah sekaligus balik nama (BBN).",
      loket: "Loket 3",
      jenis: "layanan",
      cekFisik: true,
      nonTunai: false,
      diwakilkan: false,
      label: ["Meterai Rp10.000"],
      persyaratan: [
        s("Fotokopi KTP pemilik kendaraan lama", "2 lembar"),
        s("Fotokopi KTP alamat tujuan yang baru", "2 lembar"),
        s("Fotokopi STNK dan BPKB (lampirkan yang asli)", "2 lembar"),
        s("Bawa kendaraan untuk cek fisik", "—"),
        s("Kuitansi pembelian bertanda tangan di atas meterai Rp10.000", "1 lembar"),
        s("Map", "1 buah"),
      ],
      catatan:
        "Kendaraan sudah berada di tempat baru? Ajukan permohonan cek fisik bantuan di Samsat tujuan.",
      unggulan: false,
      alur: { status: "persetujuan", penyedia: "Seksi Pendataan & Penetapan", mulai: 4 },
    },
    {
      nama: "Duplikat STNK hilang atau rusak",
      slug: "duplikat-stnk",
      ringkasan: "Penerbitan ulang STNK yang hilang atau rusak.",
      loket: "Loket 2",
      jenis: "layanan",
      cekFisik: true,
      nonTunai: false,
      diwakilkan: true,
      label: ["Laporan polisi asli"],
      persyaratan: [
        s("Surat keterangan STNK hilang/rusak dari kepolisian (asli)", "1 lembar"),
        s("Surat pernyataan pribadi STNK hilang/rusak bermeterai Rp10.000", "1 lembar"),
        s("Fotokopi KTP pemilik (sesuai alamat di STNK dan BPKB)", "2 lembar"),
        s("Fotokopi BPKB halaman 2–3 (lampirkan yang asli)", "2 lembar"),
        s("Map", "1 buah"),
        s("Bawa kendaraan untuk cek fisik", "—"),
      ],
      catatan:
        "Jika mewakili, sertakan surat kuasa bertanda tangan di atas meterai Rp10.000 (1 lembar).",
      unggulan: false,
      alur: { status: "terbit", penyedia: "Seksi Pendataan & Penetapan", mulai: 20 },
    },
    {
      nama: "Tambahan untuk badan hukum",
      slug: "tambahan-badan-hukum",
      ringkasan: "Dokumen tambahan bila kendaraan atas nama perusahaan.",
      loket: "Semua loket",
      jenis: "tambahan",
      cekFisik: false,
      nonTunai: false,
      diwakilkan: false,
      label: [],
      persyaratan: [
        s("Fotokopi akta pendirian perusahaan", "1 lembar"),
        s("Fotokopi SITU/SIUP", "1 lembar"),
        s("Fotokopi NIB (Nomor Induk Berusaha)", "1 lembar"),
        s("Fotokopi NPWP", "1 lembar"),
        s("Keterangan domisili perusahaan dari kelurahan setempat", "1 lembar"),
        s("Fotokopi KTP penanggung jawab perusahaan", "1 lembar"),
      ],
      catatan:
        "Layanan lain (mis. balik nama dalam daerah) dapat ditanyakan di meja informasi.",
      unggulan: false,
      alur: { status: "terbit", penyedia: "Seksi Pendataan & Penetapan", mulai: 20 },
    },
  ]
  return data.map(({ alur: o, ...d }, i) => ({ id: newId(), urutan: i, ...d, ...alur(sekarang, o) }))
}

function faq(sekarang: Date): Faq[] {
  const data: Array<[string, string]> = [
    [
      "Apakah pembayaran pajak tahunan bisa diwakilkan?",
      "Bisa. Bawa berkas lengkap atas nama pemilik. Untuk perpanjangan STNK 5 tahun dan duplikat STNK, sertakan surat kuasa bermeterai Rp10.000.",
    ],
    [
      "Kapan kendaraan harus dibawa untuk cek fisik?",
      "Untuk perpanjangan STNK 5 tahun, mutasi keluar, balik nama, dan duplikat STNK. Pajak tahunan tidak memerlukan cek fisik.",
    ],
    [
      "Berapa lembar fotokopi yang perlu disiapkan?",
      "Umumnya dua lembar untuk setiap dokumen. Jumlah pasti untuk tiap layanan tercantum di halaman persyaratan.",
    ],
    [
      "Apakah saya bisa membayar tanpa uang tunai?",
      "Bisa. Ikuti alur pembayaran non-tunai di kasir. Simpan bukti transaksi sampai STNK selesai disahkan.",
    ],
    [
      "STNK saya hilang. Apa yang harus disiapkan?",
      "Surat keterangan kehilangan asli dari kepolisian, surat pernyataan bermeterai, fotokopi KTP dan BPKB, serta kendaraan untuk cek fisik. Badan hukum menambahkan dokumen perusahaan.",
    ],
    [
      "Di mana jadwal Samsat Keliling diumumkan?",
      "Di halaman jadwal. Petugas memperbarui lokasi setiap Jumat untuk minggu berikutnya.",
    ],
    [
      "Bagaimana cara menyampaikan pengaduan?",
      "Lewat formulir kontak, kotak saran di kantor, atau SP4N-LAPOR!. Identitas pelapor kami jaga kerahasiaannya.",
    ],
  ]
  return data.map(([pertanyaan, jawaban], i) => ({
    id: newId(),
    urutan: i,
    dibuat: waktu(sekarang, 30),
    diperbarui: waktu(sekarang, 30 - i),
    pertanyaan,
    jawaban,
    tampil: true,
  }))
}

/** Rotasi Samsat Keliling + titik perkiraan (contoh) untuk data absensi */
const ROTASI_KELILING: Array<[Hari, string, string, string, string, number, number]> = [
  [1, "08:00", "12:00", "Kantor Kelurahan Oebobo", "Kec. Oebobo", -10.1655, 123.6107],
  [1, "13:00", "15:00", "Kantor Kelurahan Fatululi", "Kec. Oebobo", -10.1725, 123.6185],
  [2, "08:00", "12:00", "Kantor Kelurahan Oesapa", "Kec. Kelapa Lima", -10.146, 123.636],
  [2, "13:00", "15:00", "Kantor Kelurahan Sikumana", "Kec. Maulafa", -10.2002, 123.615],
  [3, "08:00", "12:00", "Kantor Kelurahan Penfui", "Kec. Maulafa", -10.169, 123.664],
  [3, "13:00", "15:00", "Kantor Kelurahan Liliba", "Kec. Oebobo", -10.184, 123.642],
  [4, "08:00", "12:00", "Kantor Kelurahan Naikoten I", "Kec. Kota Raja", -10.169, 123.588],
  [4, "13:00", "15:00", "Kantor Kelurahan Oepura", "Kec. Maulafa", -10.188, 123.596],
  [5, "08:00", "11:00", "Kantor Kelurahan Alak", "Kec. Alak", -10.188, 123.534],
  [5, "08:00", "11:00", "Kantor Kelurahan Fontein", "Kec. Kota Raja", -10.162, 123.581],
]

function keliling(sekarang: Date): JadwalKeliling[] {
  return ROTASI_KELILING.map(([hari, mulai, selesai, lokasi, kecamatan], i) => ({
    id: newId(),
    urutan: i,
    dibuat: waktu(sekarang, 45),
    diperbarui: waktu(sekarang, 8, 15, 30),
    hari,
    mulai,
    selesai,
    lokasi,
    kecamatan,
    layanan: "Pajak tahunan",
    catatan: "",
    aktif: true,
  }))
}

function penyesuaian(sekarang: Date): PenyesuaianJadwal[] {
  const data: Array<Omit<PenyesuaianJadwal, "id" | "dibuat" | "diperbarui" | "urutan">> = [
    {
      tanggal: "2026-10-16",
      jenis: "jam-khusus",
      mulai: "08:00",
      selesai: "11:30",
      keterangan: "Apel bersama · penerimaan berkas hingga 10.30",
    },
    {
      tanggal: "2026-12-25",
      jenis: "tutup",
      mulai: "",
      selesai: "",
      keterangan: "Hari Raya Natal",
    },
  ]
  return data.map((d, i) => ({
    id: newId(),
    urutan: i,
    dibuat: waktu(sekarang, 10),
    diperbarui: waktu(sekarang, 10),
    ...d,
  }))
}

function unit(sekarang: Date): UnitKerja[] {
  const ku = newId()
  const data: Array<Omit<UnitKerja, "dibuat" | "diperbarui" | "urutan">> = [
    {
      id: ku,
      nama: "Kepala UPTD",
      singkatan: "KU",
      jenis: "pimpinan",
      induk: null,
      jabatan: "Kepala UPTD Pendapatan Daerah Wilayah Kota Kupang",
      pejabat: "[Nama pejabat]",
      nip: "",
      foto: null,
      anggota: [],
      uraianTugas:
        "Memimpin, mengoordinasikan, dan mengawasi pelaksanaan tugas teknis operasional pendapatan daerah di wilayah Kota Kupang, serta menyetujui informasi sebelum dipublikasikan.",
      catatan: "",
    },
    {
      id: newId(),
      nama: "Sub Bagian Tata Usaha",
      singkatan: "TU",
      jenis: "tata-usaha",
      induk: ku,
      jabatan: "Kepala Sub Bagian",
      pejabat: "[Nama]",
      nip: "",
      foto: null,
      anggota: [
        "Pengelola kepegawaian",
        "Pengelola keuangan",
        "Pengelola barang & perlengkapan",
        "Penata kelola sistem & teknologi informasi",
      ],
      uraianTugas:
        "Merencanakan dan melaksanakan kegiatan ketatausahaan meliputi urusan kepegawaian, keuangan, perlengkapan, dan tata usaha umum, serta memberikan pelayanan administratif kepada semua unsur di lingkungan UPTD.",
      catatan: "Pengelolaan portal PINTU berada di bawah unit ini.",
    },
    {
      id: newId(),
      nama: "Seksi Pendataan & Penetapan",
      singkatan: "S1",
      jenis: "seksi",
      induk: ku,
      jabatan: "Kepala Seksi",
      pejabat: "[Nama]",
      nip: "",
      foto: null,
      anggota: ["Petugas pendataan", "Petugas penetapan & korektor", "Petugas loket pelayanan"],
      uraianTugas:
        "Menghimpun dan memutakhirkan data objek serta subjek pajak, menetapkan pajak terutang, dan menerbitkan notis pajak kendaraan bermotor.",
      catatan: "",
    },
    {
      id: newId(),
      nama: "Seksi Penagihan & Pelaporan",
      singkatan: "S2",
      jenis: "seksi",
      induk: ku,
      jabatan: "Kepala Seksi",
      pejabat: "[Nama]",
      nip: "",
      foto: null,
      anggota: [
        "Kasir & bendahara penerimaan",
        "Petugas penagihan & Samsat Keliling",
        "Petugas pembukuan & pelaporan",
      ],
      uraianTugas:
        "Menerima dan menyetorkan pembayaran, menagih tunggakan, menyelenggarakan Samsat Keliling, serta menyusun pembukuan dan laporan penerimaan.",
      catatan: "",
    },
    {
      id: newId(),
      nama: "Kelompok Jabatan Fungsional",
      singkatan: "JF",
      jenis: "fungsional",
      induk: ku,
      jabatan: "",
      pejabat: "",
      nip: "",
      foto: null,
      anggota: [],
      uraianTugas:
        "Melaksanakan tugas sesuai keahlian dan jabatan fungsional masing-masing untuk mendukung pelayanan UPTD.",
      catatan: "Sesuai formasi yang ditetapkan.",
    },
    {
      id: newId(),
      nama: "Polri · Satlantas",
      singkatan: "PL",
      jenis: "mitra",
      induk: null,
      jabatan: "",
      pejabat: "",
      nip: "",
      foto: null,
      anggota: [],
      uraianTugas: "Registrasi dan identifikasi kendaraan bermotor, termasuk pengesahan STNK.",
      catatan: "Mitra satu atap Samsat · garis koordinasi, bukan garis komando.",
    },
    {
      id: newId(),
      nama: "PT Jasa Raharja",
      singkatan: "JR",
      jenis: "mitra",
      induk: null,
      jabatan: "",
      pejabat: "",
      nip: "",
      foto: null,
      anggota: [],
      uraianTugas: "Pengelolaan Sumbangan Wajib Dana Kecelakaan Lalu Lintas Jalan (SWDKLLJ).",
      catatan: "Mitra satu atap Samsat · garis koordinasi, bukan garis komando.",
    },
  ]
  return data.map((d, i) => ({
    ...d,
    urutan: i,
    dibuat: waktu(sekarang, 40),
    diperbarui: waktu(sekarang, 40 - i),
  }))
}

function pesan(sekarang: Date): Pesan[] {
  const rnd = acak(2026)
  const contoh: Array<[JenisPesan, string, string]> = [
    ["pertanyaan", "Samsat Keliling", "Apakah Samsat Keliling melayani perpanjangan 5 tahun?"],
    ["pengaduan", "Mutasi & balik nama", "Antrean di loket mutasi lama karena berkas kurang lengkap."],
    ["masukan", "Umum / portal PINTU", "Mohon tambahkan contoh pengisian surat kuasa."],
    ["pertanyaan", "Pajak tahunan", "Apakah pajak tahunan bisa dibayar oleh keluarga tanpa surat kuasa?"],
    ["pertanyaan", "Samsat Keliling", "Minggu depan Samsat Keliling ke kelurahan mana saja?"],
    ["pengaduan", "Pajak tahunan", "Mesin antrean sempat mati sehingga nomor antrean dipanggil manual."],
    ["masukan", "Umum / portal PINTU", "Tolong cantumkan nomor loket di setiap persyaratan."],
    ["pertanyaan", "Duplikat STNK", "Surat kehilangan dari polsek atau polres yang berlaku?"],
    ["pertanyaan", "Perpanjangan STNK 5 tahun", "Kalau kendaraan rusak, apakah cek fisik bisa didatangi petugas?"],
    ["pengaduan", "Perpanjangan STNK 5 tahun", "Ada orang di parkiran yang menawarkan jasa urus cepat."],
    ["masukan", "Pajak tahunan", "Kursi di ruang tunggu kurang saat jam ramai."],
    ["pertanyaan", "Pajak tahunan", "Apakah QRIS bisa dipakai untuk semua bank?"],
    ["pertanyaan", "Mutasi & balik nama", "Berapa lama proses mutasi keluar sampai selesai?"],
    ["masukan", "Samsat Keliling", "Mohon Samsat Keliling juga dijadwalkan ke Kelurahan Bakunase."],
    ["pengaduan", "Samsat Keliling", "Samsat Keliling di kelurahan kami tutup lebih awal dari jadwal."],
    ["pertanyaan", "Umum / portal PINTU", "Formulir surat kuasa bisa diunduh di mana?"],
  ]
  const nama = [
    "Wilhelmus",
    "Ratna",
    "Agustinus",
    "Maria",
    "Yohanes",
    "Fransiska",
    "Daniel",
    "Selfina",
    "Yosef",
    "Melkior",
    "Theresia",
    "Imanuel",
    "",
    "",
  ]
  const sumber: SumberPesan[] = ["formulir", "formulir", "formulir", "whatsapp", "kotak-saran", "lapor"]
  const balasan: Record<JenisPesan, string> = {
    pertanyaan:
      "Terima kasih atas pertanyaannya. Informasi lengkap sudah kami kirimkan lewat kontak yang Anda cantumkan dan tersedia di halaman persyaratan PINTU.",
    masukan:
      "Terima kasih atas masukannya. Usulan Anda kami catat sebagai bahan evaluasi dan pemutakhiran portal.",
    pengaduan:
      "Terima kasih atas laporannya. Pengaduan sudah kami teruskan ke unit terkait dan ditindaklanjuti sesuai standar pelayanan.",
  }
  const hasil: Pesan[] = []
  // tiga pesan terbaru mengikuti contoh di ruang pegawai portal
  const terbaru: Array<[number, number]> = [
    [0, 10],
    [1, 60],
    [2, 60 * 22],
  ]
  terbaru.forEach(([i, menitLalu], n) => {
    const [jenis, lay, isi] = contoh[i]
    const diterima = new Date(sekarang.getTime() - menitLalu * 60000).toISOString()
    hasil.push({
      id: newId(),
      urutan: n,
      dibuat: diterima,
      diperbarui: diterima,
      tiket: tiketDari(rnd),
      jenis,
      nama: ["Wilhelmus", "Ratna", "Agustinus"][n],
      kontak: ["wilhelmus@contoh.id", "+62 812-0000-0001", "agustinus@contoh.id"][n],
      layanan: lay,
      isi,
      sumber: "formulir",
      status: "baru",
      diterima,
      ditangani: "",
      tanggapan: [],
    })
  })
  // sisanya tersebar dalam 90 hari terakhir
  for (let n = 3; n < 46; n++) {
    const [jenis, lay, isi] = contoh[Math.floor(rnd() * contoh.length)]
    const hariLalu = Math.floor(rnd() ** 1.35 * 89) + 1
    const diterimaIso = waktu(sekarang, hariLalu, 8 + Math.floor(rnd() * 8), Math.floor(rnd() * 60))
    const status: StatusPesan = hariLalu > 6 ? "selesai" : rnd() > 0.45 ? "diproses" : "baru"
    const nm = nama[Math.floor(rnd() * nama.length)]
    const tanggapan =
      status === "selesai"
        ? [
            {
              id: newId(),
              isi: balasan[jenis],
              oleh: OPERATOR,
              waktu: new Date(new Date(diterimaIso).getTime() + (4 + rnd() * 40) * 3600000).toISOString(),
            },
          ]
        : []
    hasil.push({
      id: newId(),
      urutan: n,
      dibuat: diterimaIso,
      diperbarui: tanggapan[0]?.waktu ?? diterimaIso,
      tiket: tiketDari(rnd),
      jenis,
      nama: nm,
      kontak: nm ? `${nm.toLowerCase()}@contoh.id` : "+62 813-0000-0000",
      layanan: lay,
      isi,
      sumber: sumber[Math.floor(rnd() * sumber.length)],
      status,
      diterima: diterimaIso,
      ditangani: status === "baru" ? "" : OPERATOR,
      tanggapan,
    })
  }
  return hasil.sort((a, b) => b.diterima.localeCompare(a.diterima)).map((p, i) => ({ ...p, urutan: i }))
}

/** Contoh absensi tiga hari kerja terakhir, mengikuti rotasi Samsat Keliling (tanpa foto) */
function absensi(sekarang: Date): Absensi[] {
  const rnd = acak(1016)
  const hasil: Absensi[] = []
  let hariKerja = 0
  for (let hariLalu = 1; hariLalu <= 10 && hariKerja < 3; hariLalu++) {
    const pagi = waktu(sekarang, hariLalu, 9)
    const hari = new Date(new Date(pagi).getTime() + 8 * 3600000).getUTCDay()
    const slot = ROTASI_KELILING.find(([h]) => h === hari)
    if (!slot) continue
    hariKerja++
    const [, mulai, selesai, lokasi, , lat, lng] = slot
    const kelurahan = lokasi.replace("Kantor Kelurahan ", "")
    const petugas = hariKerja === 2 ? "Petugas Seksi Penagihan" : OPERATOR
    for (const tipe of ["datang", "pulang"] as const) {
      const [jam, menit] = (tipe === "datang" ? mulai : selesai).split(":").map(Number)
      const geser = tipe === "datang" ? -Math.floor(4 + rnd() * 10) : Math.floor(2 + rnd() * 12)
      const w = waktu(sekarang, hariLalu, jam, menit + geser)
      hasil.push({
        id: newId(),
        urutan: 0,
        dibuat: w,
        diperbarui: w,
        tipe,
        kegiatan: `Samsat Keliling · ${kelurahan}`,
        jenis: "keliling",
        waktu: w,
        petugas,
        nip: "",
        foto: null,
        lokasi: {
          lat: Number((lat + (rnd() - 0.5) * 0.0004).toFixed(6)),
          lng: Number((lng + (rnd() - 0.5) * 0.0004).toFixed(6)),
          akurasi: Math.round(6 + rnd() * 14),
        },
        tempat: `Halaman ${lokasi}`,
        catatan: tipe === "pulang" ? `Layanan selesai, ${Math.round(38 + rnd() * 30)} transaksi.` : "",
      })
    }
  }
  return hasil.sort((a, b) => b.waktu.localeCompare(a.waktu)).map((a, i) => ({ ...a, urutan: i }))
}

function tiketDari(rnd: () => number): string {
  const huruf = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789"
  let t = "PINTU-"
  for (let i = 0; i < 6; i++) t += huruf[Math.floor(rnd() * huruf.length)]
  return t
}

function halaman(sekarang: Date): Halaman[] {
  return [
    {
      id: newId(),
      urutan: 0,
      dibuat: waktu(sekarang, 40),
      diperbarui: waktu(sekarang, 40),
      slug: "kebijakan-privasi",
      judul: "Data Anda secukupnya.",
      judulLanjutan: "Dijaga sepenuhnya.",
      pengantar: "Kebijakan privasi · berlaku sejak [tanggal terbit]",
      isi: `<p>Kebijakan ini menjelaskan data apa yang dikumpulkan portal PINTU, untuk apa, dan bagaimana kami melindunginya. Kebijakan mengacu pada Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi.</p><h2>Data yang kami kumpulkan.</h2><ul><li><p>Data yang Anda isi di formulir masukan: nama (opsional), email atau nomor WhatsApp, dan isi pesan.</p></li><li><p>Data teknis anonim: halaman yang dikunjungi dan jenis perangkat, untuk mengukur kebutuhan informasi.</p></li><li><p>Portal tidak meminta NIK, nomor rekening, atau data kendaraan untuk sekadar membaca informasi.</p></li></ul><h2>Untuk apa data dipakai.</h2><p>Untuk menjawab pertanyaan, menindaklanjuti pengaduan, dan mengevaluasi kualitas informasi. Data tidak dijual dan tidak dibagikan kepada pihak lain di luar keperluan penanganan pengaduan.</p><h2>Berapa lama disimpan.</h2><p>Pesan dan pengaduan disimpan selama diperlukan untuk tindak lanjut dan pelaporan, sesuai ketentuan kearsipan yang berlaku.</p><h2>Hak Anda.</h2><p>Anda dapat meminta akses, perbaikan, atau penghapusan data melalui <a href="kontak.html#masukan">formulir kontak</a>.</p><blockquote><p>Petugas kami tidak pernah meminta kata sandi, PIN, atau kode OTP Anda.</p></blockquote><p><em>Draf kebijakan. Wajib ditinjau unit hukum sebelum terbit.</em></p>`,
    },
    {
      id: newId(),
      urutan: 1,
      dibuat: waktu(sekarang, 40),
      diperbarui: waktu(sekarang, 40),
      slug: "syarat-ketentuan",
      judul: "Informasi resmi.",
      judulLanjutan: "Dipakai dengan bijak.",
      pengantar: "Syarat & ketentuan · berlaku sejak [tanggal terbit]",
      isi: `<h2>Sifat informasi.</h2><p>Informasi di portal ini bersifat panduan. Nilai pajak yang mengikat adalah yang tercetak pada notis pajak resmi. Hasil simulasi hanya perkiraan.</p><h2>Pemutakhiran.</h2><p>Setiap halaman mencantumkan tanggal pemutakhiran. Bila terdapat perbedaan dengan papan informasi di loket, informasi terbaru yang telah disetujui pimpinan yang berlaku.</p><h2>Penggunaan konten.</h2><p>Konten boleh dikutip untuk keperluan non-komersial dengan menyebutkan sumber. Lambang daerah tidak boleh diubah atau dipakai untuk kepentingan lain.</p><h2>Tautan pihak ketiga.</h2><p>Portal dapat menautkan ke situs mitra seperti SP4N-LAPOR!, Ombudsman, atau bank mitra. Kebijakan situs tersebut berada di luar tanggung jawab kami.</p><p><em>Draf ketentuan. Wajib ditinjau unit hukum sebelum terbit.</em></p>`,
    },
  ]
}

function pemutakhiran(sekarang: Date): Pemutakhiran[] {
  const data: Array<Omit<Pemutakhiran, "id" | "dibuat" | "diperbarui" | "urutan">> = [
    { konten: "Papan layanan hari ini", modul: "papan", unit: "Petugas loket", frekuensi: "Setiap hari kerja, 07.45 WITA", intervalHari: 1 },
    { konten: "Jadwal Samsat Keliling", modul: "keliling", unit: "Seksi Penagihan & Pelaporan", frekuensi: "Mingguan, setiap Jumat", intervalHari: 7 },
    { konten: "Dokumentasi kegiatan", modul: "dokumentasi", unit: "Semua unit", frekuensi: "Setiap kegiatan", intervalHari: 14 },
    { konten: "Berita & pengumuman", modul: "berita", unit: "Pengelola publikasi", frekuensi: "Minimal 2 per bulan", intervalHari: 15 },
    { konten: "Jam pelayanan", modul: "jam", unit: "Sub Bagian Tata Usaha", frekuensi: "Bulanan", intervalHari: 30 },
    { konten: "Persyaratan layanan", modul: "layanan", unit: "Seksi Pendataan & Penetapan", frekuensi: "Saat regulasi berubah · tinjau tiap 3 bulan", intervalHari: 90 },
    { konten: "Regulasi & unduhan", modul: "dokumen", unit: "Sub Bagian Tata Usaha", frekuensi: "Saat regulasi berubah · tinjau tiap 6 bulan", intervalHari: 180 },
    { konten: "Profil instansi", modul: "profil", unit: "Sub Bagian Tata Usaha", frekuensi: "Tahunan", intervalHari: 365 },
  ]
  return data.map((d, i) => ({
    id: newId(),
    urutan: i,
    dibuat: waktu(sekarang, 40),
    diperbarui: waktu(sekarang, 40),
    ...d,
  }))
}

function pengaturan(sekarang: Date): Pengaturan {
  const id = newId
  return {
    situs: {
      namaPortal: "PINTU",
      kepanjangan: "Portal Informasi dan Pelayanan",
      namaInstansi: "UPTD Pendapatan Daerah Wilayah Kota Kupang",
      namaSingkat: "Samsat Kota Kupang",
      induk: "Badan Pendapatan dan Aset Daerah Provinsi Nusa Tenggara Timur",
      deskripsi:
        "Unit Pelaksana Teknis Daerah pada Badan Pendapatan dan Aset Daerah Provinsi Nusa Tenggara Timur. Kantor bersama Samsat Kota Kupang.",
      urlPortal: "../",
      hakCipta: "© 2026 UPTD Pendapatan Daerah Wilayah Kota Kupang · BPAD Provinsi NTT",
      bilahPengumuman: {
        aktif: true,
        teks: "Program pemutihan denda PKB diperpanjang hingga 31 Desember 2026.",
        tautanLabel: "Baca",
        tautanUrl: "berita-detail.html",
      },
    },
    beranda: {
      hero: {
        eyebrow: "UPTD Pendapatan Daerah Wilayah Kota Kupang · Samsat Kota Kupang",
        judul: "Semua informasi Samsat.",
        lanjutan: "Lewat satu pintu.",
        deskripsi:
          "PINTU adalah portal resmi informasi dan pelayanan pajak kendaraan di Kota Kupang. Cek persyaratan, jadwal, dan lokasi layanan sebelum Anda berangkat.",
        tombolLabel: "Cek Persyaratan Layanan",
        tombolUrl: "layanan.html",
        catatan: "Gratis dan tanpa perantara. Setiap informasi diverifikasi petugas UPTD.",
      },
      nilai: [
        { id: id(), judul: "Persyaratan jelas.", deskripsi: "Daftar berkas setiap layanan, lengkap dengan jumlah lembar fotokopi yang perlu dibawa." },
        { id: id(), judul: "Jadwal terbaru.", deskripsi: "Jam loket dan lokasi Samsat Keliling diperbarui petugas setiap hari kerja." },
        { id: id(), judul: "Tanpa perantara.", deskripsi: "Semua biaya mengikuti notis pajak resmi. Urus sendiri dengan mudah, tanpa calo." },
      ],
      layananUtama: {
        judul: "Dibuat untuk wajib pajak.",
        lanjutan: "Dirancang agar cukup sekali datang.",
        deskripsi: "Pilih layanan, siapkan berkas dari rumah, lalu datang ke loket yang tepat.",
      },
      mitra: [
        { id: id(), singkatan: "BP", nama: "BPAD Provinsi NTT" },
        { id: id(), singkatan: "PL", nama: "Polri · Satlantas" },
        { id: id(), singkatan: "JR", nama: "Jasa Raharja" },
        { id: id(), singkatan: "BN", nama: "Bank NTT" },
        { id: id(), singkatan: "QR", nama: "QRIS" },
        { id: id(), singkatan: "SG", nama: "SIGNAL" },
      ],
      kanal: [
        { id: id(), kelompok: "Kantor", item: ["Loket pajak tahunan", "Loket 5 tahunan", "Loket mutasi & BBN", "Cek fisik kendaraan"] },
        { id: id(), kelompok: "Keliling", item: ["Samsat Keliling", "Layanan jemput bola"] },
        { id: id(), kelompok: "Digital", item: ["QRIS di kasir", "Bank NTT", "SIGNAL"] },
        { id: id(), kelompok: "Informasi", item: ["Portal PINTU", "Media sosial", "WhatsApp layanan"] },
        { id: id(), kelompok: "Pengaduan", item: ["SP4N-LAPOR!", "Kotak saran", "Ombudsman NTT"] },
      ],
      integritas: {
        judul: "Bayar sesuai notis.",
        lanjutan: "Tidak lebih sepeser pun.",
        deskripsi:
          "Seluruh pembayaran tercatat di sistem dan dapat dilakukan non-tunai. Petugas kami tidak pernah meminta uang atau data pribadi lewat pesan pribadi.",
      },
      biaya: {
        judul: "Biaya jelas.",
        lanjutan: "Tanpa kejutan.",
        deskripsi:
          "Pajak kendaraan dihitung sistem dan tercetak di notis. Layanan pendukung di kantor ini tidak dipungut biaya.",
        catatanNotis: "Bayar sesuai notis. Tunai di kasir atau non-tunai lewat QRIS.",
        wajibDibawa: "KTP asli dan STNK asli pemilik kendaraan.",
        jaminan: ["Bukti bayar resmi", "Bisa non-tunai", "Tercatat di sistem"],
      },
      penutup: {
        judul: "Map apa saja boleh.",
        lanjutan: "Asal jangan lupa dibawa.",
        deskripsi: "Hampir setiap layanan meminta satu map. Kami hanya mengingatkan.",
      },
    },
    papan: {
      nomorAntrean: "A-042",
      layananAntrean: "Loket 1 · Pajak tahunan",
      waktuTunggu: 18,
      selisihKemarin: -6,
      loket: [
        { id: id(), kode: "L1", nama: "Pajak tahunan", status: "buka" },
        { id: id(), kode: "L2", nama: "Perpanjangan 5 tahun", status: "buka" },
        { id: id(), kode: "L3", nama: "Mutasi & balik nama", status: "buka" },
        { id: id(), kode: "L4", nama: "Kasir non-tunai", status: "buka" },
      ],
      catatan: "Diperbarui petugas setiap hari kerja · 07.45 WITA",
      diperbarui: waktu(sekarang, 1, 7, 45),
    },
    jam: {
      hari: [
        { hari: 1, buka: true, mulai: "08:00", selesai: "15:00", catatan: "Berkas s.d. 14.00" },
        { hari: 2, buka: true, mulai: "08:00", selesai: "15:00", catatan: "Berkas s.d. 14.00" },
        { hari: 3, buka: true, mulai: "08:00", selesai: "15:00", catatan: "Berkas s.d. 14.00" },
        { hari: 4, buka: true, mulai: "08:00", selesai: "15:00", catatan: "Berkas s.d. 14.00" },
        { hari: 5, buka: true, mulai: "08:00", selesai: "11:30", catatan: "Berkas s.d. 10.30" },
        { hari: 6, buka: true, mulai: "08:00", selesai: "12:00", catatan: "Pajak tahunan saja" },
        { hari: 0, buka: false, mulai: "", selesai: "", catatan: "Minggu & hari libur" },
      ],
      batasBerkasMenit: 60,
      catatan:
        "Antrean biasanya paling ramai pukul 09.00–11.00. Penerimaan berkas ditutup satu jam sebelum loket tutup.",
      pemberitahuan: "Perubahan jadwal diumumkan paling lambat H-3.",
    },
    tarif: {
      opsenPersen: 66,
      dendaPersenPerBulan: 1,
      maksBulanDenda: 24,
      swdklljMotor: 35000,
      swdklljMobil: 143000,
      contohPokok: 285000,
      contohJenis: "motor",
      contohTelat: 3,
      catatan: "Asumsi contoh. Wajib diverifikasi petugas sebelum terbit. Tagihan resmi mengikuti notis pajak di loket.",
    },
    statistik: {
      status: "ilustrasi",
      catatan: "Data ilustrasi untuk keperluan desain. Angka resmi tampil setelah diverifikasi dan disetujui.",
      tahun: "2026",
      set: [
        {
          kunci: "transaksi",
          label: "Transaksi pelayanan",
          sub: "Transaksi pelayanan · UPTD Kota Kupang",
          judulGrafik: "Transaksi per bulan",
          satuan: "transaksi",
          tiles: [
            { id: id(), label: "Total transaksi", nilai: 12480, satuan: "", perubahan: 4.2, naikBaik: true },
            { id: id(), label: "Pajak tahunan", nilai: 9316, satuan: "", perubahan: 3.1, naikBaik: true },
            { id: id(), label: "Perpanjangan 5 tahun", nilai: 2104, satuan: "", perubahan: 6.8, naikBaik: true },
            { id: id(), label: "Mutasi & BBN", nilai: 1060, satuan: "", perubahan: -2.4, naikBaik: true },
          ],
          seri: [880, 910, 1020, 960, 1040, 1100, 990, 1080, 1150, 1120, 1060, 1160],
        },
        {
          kunci: "penerimaan",
          label: "Penerimaan pajak",
          sub: "Penerimaan pajak daerah · UPTD Kota Kupang",
          judulGrafik: "Penerimaan PKB per bulan",
          satuan: "juta rupiah",
          tiles: [
            { id: id(), label: "Penerimaan PKB", nilai: 18.4, satuan: "M", perubahan: 5.6, naikBaik: true },
            { id: id(), label: "BBNKB", nilai: 6.1, satuan: "M", perubahan: -1.8, naikBaik: true },
            { id: id(), label: "Opsen PKB (Pemkot)", nilai: 12.1, satuan: "M", perubahan: 5.6, naikBaik: true },
            { id: id(), label: "Capaian target", nilai: 78, satuan: "%", perubahan: 3, naikBaik: true },
          ],
          seri: [1310, 1380, 1520, 1460, 1590, 1640, 1500, 1620, 1710, 1680, 1600, 1740],
        },
        {
          kunci: "keliling",
          label: "Samsat Keliling",
          sub: "Samsat Keliling · 6 kecamatan, 51 kelurahan",
          judulGrafik: "Transaksi keliling per bulan",
          satuan: "transaksi",
          tiles: [
            { id: id(), label: "Titik layanan", nilai: 22, satuan: "", perubahan: 10, naikBaik: true },
            { id: id(), label: "Transaksi keliling", nilai: 1284, satuan: "", perubahan: 12.5, naikBaik: true },
            { id: id(), label: "Rata-rata per titik", nilai: 58, satuan: "", perubahan: 2.1, naikBaik: true },
            { id: id(), label: "Kelurahan terjangkau", nilai: 31, satuan: "/51", perubahan: 8, naikBaik: true },
          ],
          seri: [64, 72, 88, 95, 102, 110, 98, 120, 131, 126, 118, 140],
        },
        {
          kunci: "kepuasan",
          label: "Kepuasan masyarakat",
          sub: "Survei Kepuasan Masyarakat (IKM)",
          judulGrafik: "Waktu tunggu rata-rata per bulan",
          satuan: "menit",
          tiles: [
            { id: id(), label: "Indeks kepuasan", nilai: 88.4, satuan: "", perubahan: 1.6, naikBaik: true },
            { id: id(), label: "Waktu tunggu rata-rata", nilai: 18, satuan: " mnt", perubahan: -6, naikBaik: false },
            { id: id(), label: "Pengaduan selesai", nilai: 96, satuan: "%", perubahan: 2, naikBaik: true },
            { id: id(), label: "Responden survei", nilai: 412, satuan: "", perubahan: 9.3, naikBaik: true },
          ],
          seri: [31, 30, 28, 29, 26, 25, 24, 23, 22, 21, 19, 18],
        },
      ],
    },
    profil: {
      judul: "Melayani pajak daerah.",
      lanjutan: "Dari jantung Kota Kupang.",
      pengantar:
        "Kami unit pelaksana teknis yang mengurus pajak kendaraan bermotor bagi warga Kota Kupang, bersama mitra Samsat dalam satu atap.",
      foto: null,
      keteranganFoto:
        "Kantor Bersama Samsat Kota Kupang. Tempat pajak, STNK, dan asuransi kecelakaan diurus sekaligus.",
      kedudukan: {
        judul: "Bagian dari BPAD NTT.",
        lanjutan: "Dekat dengan warga kota.",
        deskripsi:
          "UPTD berada di bawah Badan Pendapatan dan Aset Daerah Provinsi Nusa Tenggara Timur dan melayani seluruh wilayah Kota Kupang.",
      },
      identitas: [
        { id: id(), label: "Nama unit", nilai: "UPTD Pendapatan Daerah Wilayah Kota Kupang" },
        { id: id(), label: "Dikenal sebagai", nilai: "Samsat Kota Kupang" },
        { id: id(), label: "Induk organisasi", nilai: "Badan Pendapatan dan Aset Daerah Provinsi NTT" },
        { id: id(), label: "Wilayah kerja", nilai: "Kota Kupang · 6 kecamatan, 51 kelurahan" },
        { id: id(), label: "Mitra satu atap", nilai: "Polri (Satlantas) dan PT Jasa Raharja" },
        { id: id(), label: "Pajak yang dilayani", nilai: "PKB, BBNKB, serta opsen untuk Pemerintah Kota" },
        { id: id(), label: "Kanal pembayaran", nilai: "Kasir tunai, QRIS, dan bank mitra" },
      ],
      tugasFungsi: [
        { id: id(), judul: "Pendataan objek dan subjek pajak kendaraan bermotor di Kota Kupang.", deskripsi: "Termasuk pemutakhiran data kepemilikan dan alamat.", kataKunci: "Data" },
        { id: id(), judul: "Penetapan pajak dan penerbitan notis pajak kendaraan.", deskripsi: "Nilai pajak dihitung sistem, bukan oleh petugas loket.", kataKunci: "Tetapkan" },
        { id: id(), judul: "Pelayanan Samsat: pengesahan STNK, perpanjangan 5 tahun, mutasi, dan balik nama.", deskripsi: "Bersama Satlantas dan Jasa Raharja di kantor yang sama.", kataKunci: "Layani" },
        { id: id(), judul: "Penerimaan, penyetoran, dan pelaporan pajak daerah.", deskripsi: "Tunai di kasir atau non-tunai lewat kanal resmi.", kataKunci: "Terima" },
        { id: id(), judul: "Penagihan tunggakan serta pelaksanaan program pemutihan.", deskripsi: "Termasuk Samsat Keliling ke kelurahan.", kataKunci: "Jangkau" },
        { id: id(), judul: "Ketatausahaan: kepegawaian, keuangan, perlengkapan, dan layanan administrasi internal.", deskripsi: "Dijalankan Sub Bagian Tata Usaha.", kataKunci: "Kelola" },
      ],
      sambutan: {
        kutipan:
          "Kami ingin setiap warga datang sekali dan pulang dengan urusan selesai. PINTU adalah janji kami untuk informasi yang jelas, resmi, dan selalu terbaru.",
        nama: "[Nama Kepala UPTD]",
        jabatan: "Kepala UPTD Pendapatan Daerah Wilayah Kota Kupang",
        foto: null,
      },
    },
    visiMisi: {
      sumberVisi: "Visi Pemerintah Provinsi NTT",
      visi: "NTT Maju, Sehat, Cerdas, Sejahtera dan Berkelanjutan.",
      misi: [
        { id: id(), teks: "Memastikan infrastruktur berkelanjutan demi mewujudkan ekonomi berbasis potensi daerah yang berdaya saing.", kataKunci: "Maju" },
        { id: id(), teks: "Memperluas pelayanan kesehatan dan jaminan sosial yang lebih inklusif, terjangkau, dan mudah diakses.", kataKunci: "Sehat" },
        { id: id(), teks: "Menghadirkan pendidikan berkualitas yang merata, partisipatif, dan tepat sasaran.", kataKunci: "Cerdas" },
        { id: id(), teks: "Mewujudkan kesejahteraan sosial, kesetaraan akses, serta kualitas hidup yang berkeadilan dan madani bagi seluruh lapisan masyarakat.", kataKunci: "Sejahtera" },
        { id: id(), teks: "Mewujudkan pembangunan berkelanjutan melalui pengelolaan sumber daya alam dan manusia yang bijak serta pemenuhan hak asasi manusia untuk masa depan yang inklusif.", kataKunci: "Berkelanjutan" },
      ],
      kontribusi: [
        { id: id(), misi: "Misi 01 · Maju", judul: "Tata kelola informasi yang tertib.", deskripsi: "Alur pengumpulan, verifikasi, persetujuan, dan publikasi informasi menjadi fondasi sistem informasi pelayanan yang berkelanjutan." },
        { id: id(), misi: "Misi 04 · Sejahtera", judul: "Akses informasi yang setara.", deskripsi: "Persyaratan, jadwal, dan lokasi layanan dapat dibuka siapa saja, kapan saja, tanpa harus bertanya ke loket lebih dulu." },
      ],
      nilai: [
        { id: id(), huruf: "B", judul: "Berorientasi pelayanan", deskripsi: "Memahami kebutuhan wajib pajak. Ramah, cekatan, dan solutif." },
        { id: id(), huruf: "A", judul: "Akuntabel", deskripsi: "Jujur, cermat, disiplin, dan bertanggung jawab atas setiap transaksi." },
        { id: id(), huruf: "K", judul: "Kompeten", deskripsi: "Terus belajar agar layanan makin cepat dan tepat." },
        { id: id(), huruf: "H", judul: "Harmonis", deskripsi: "Saling menghargai, di antara rekan kerja dan dengan masyarakat." },
        { id: id(), huruf: "L", judul: "Loyal", deskripsi: "Berdedikasi dan menjaga rahasia jabatan serta nama baik instansi." },
        { id: id(), huruf: "A", judul: "Adaptif", deskripsi: "Terbuka pada perubahan dan inovasi digital." },
        { id: id(), huruf: "K", judul: "Kolaboratif", deskripsi: "Bersinergi dengan Polri, Jasa Raharja, dan bank mitra." },
      ],
      maklumat: {
        teks: "Dengan ini, kami menyatakan sanggup menyelenggarakan pelayanan sesuai standar pelayanan yang telah ditetapkan. Apabila tidak menepati janji ini, kami siap menerima sanksi sesuai peraturan perundang-undangan yang berlaku.",
        penandatangan: "Kepala UPTD Pendapatan Daerah Wilayah Kota Kupang",
        berkas: null,
      },
    },
    kontak: {
      namaKantor: "Kantor Bersama Samsat Kota Kupang",
      alamat: "Jl. [alamat kantor]",
      kota: "Kota Kupang",
      provinsi: "Nusa Tenggara Timur",
      telepon: "(0380) 000 000",
      whatsapp: "+62 8xx-xxxx-xxxx",
      whatsappCatatan: "Hanya pesan teks, hari kerja",
      email: "uptd.kotakupang@example.go.id",
      mediaSosial: [{ id: id(), platform: "Instagram", akun: "@samsatkotakupang", url: "" }],
      mapsUrl: "https://maps.google.com/?q=Samsat+Kota+Kupang",
      catatanLokasi: "Parkir tersedia di halaman kantor",
      janjiBalasan: "Kami membalas paling lambat tiga hari kerja.",
      kanalPengaduan: [
        { id: id(), nama: "SP4N-LAPOR!", deskripsi: "Sistem pengaduan pelayanan publik nasional. Aduan diteruskan ke unit kami.", tautanLabel: "Buka lapor.go.id", tautanUrl: "https://www.lapor.go.id" },
        { id: id(), nama: "Kotak saran di kantor", deskripsi: "Tersedia di ruang tunggu. Dibuka dan direkap setiap Jumat oleh Sub Bagian Tata Usaha.", tautanLabel: "Lihat jam kantor", tautanUrl: "jadwal.html" },
        { id: id(), nama: "Ombudsman RI Perwakilan NTT", deskripsi: "Bila pengaduan Anda tidak ditanggapi atau ada dugaan maladministrasi.", tautanLabel: "Buka ombudsman.go.id", tautanUrl: "https://ombudsman.go.id" },
      ],
    },
    akun: {
      nama: "Admin PINTU",
      jabatan: "Penata Kelola Sistem dan Teknologi Informasi",
      unit: "Sub Bagian Tata Usaha",
      peran: "Pengelola publikasi",
      email: "",
    },
  }
}

function aktivitas(sekarang: Date): Aktivitas[] {
  const data: Array<[number, number, number, string, string, string, string]> = [
    [0, 9, 5, OPERATOR, "memperbarui", "Papan Layanan", "Papan layanan hari ini"],
    [1, 14, 20, "Seksi Penagihan & Pelaporan", "menyiapkan draf", "Berita & Pengumuman", "Samsat Keliling menjangkau 31 kelurahan tahun ini."],
    [2, 10, 12, KEPALA, "menyetujui dan menerbitkan", "Berita & Pengumuman", "Pembayaran QRIS kini tersedia di semua kasir."],
    [3, 14, 40, KASUBBAG, "meneruskan ke penyetuju", "Persyaratan Layanan", "Mutasi keluar, ganti pemilik"],
    [8, 15, 30, OPERATOR, "memperbarui", "Samsat Keliling", "Jadwal Samsat Keliling"],
    [9, 7, 45, OPERATOR, "memperbarui", "Jam Pelayanan", "Jam pelayanan"],
  ]
  return data.map(([hari, jam, menit, oleh, aksi, modul, target]) => ({
    id: newId(),
    waktu: waktu(sekarang, hari, jam, menit),
    oleh,
    aksi,
    modul,
    target,
  }))
}

export function buatSeed(sekarang = new Date()): Snapshot {
  return {
    versi: VERSI_DATA,
    diekspor: sekarang.toISOString(),
    koleksi: {
      berita: berita(sekarang),
      dokumentasi: dokumentasi(sekarang),
      dokumen: dokumen(sekarang),
      layanan: layanan(sekarang),
      faq: faq(sekarang),
      keliling: keliling(sekarang),
      penyesuaian: penyesuaian(sekarang),
      unit: unit(sekarang),
      pesan: pesan(sekarang),
      halaman: halaman(sekarang),
      pemutakhiran: pemutakhiran(sekarang),
      absensi: absensi(sekarang),
    },
    pengaturan: pengaturan(sekarang),
    aktivitas: aktivitas(sekarang),
  }
}

/** Waktu pembaruan awal tiap halaman pengaturan (untuk status pemutakhiran) */
export function seedWaktuPengaturan(sekarang = new Date()): Record<keyof Pengaturan, string> {
  return {
    situs: waktu(sekarang, 40),
    beranda: waktu(sekarang, 40),
    papan: waktu(sekarang, 1, 7, 45),
    jam: waktu(sekarang, 9, 7, 45),
    tarif: waktu(sekarang, 40),
    statistik: waktu(sekarang, 20),
    profil: waktu(sekarang, 40),
    visiMisi: waktu(sekarang, 40),
    kontak: waktu(sekarang, 40),
    akun: waktu(sekarang, 40),
  }
}
