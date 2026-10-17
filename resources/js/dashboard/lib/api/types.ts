/*
 * Model data PINTU.
 *
 * Setiap tipe di bawah mewakili satu jenis konten yang tampil di portal publik.
 * "Koleksi" berisi banyak entri (berita, dokumen, jadwal keliling, ...),
 * sedangkan "Pengaturan" berisi satu dokumen per halaman (profil, kontak, ...).
 * Bentuk JSON ini juga menjadi kontrak REST API saat dashboard disambungkan
 * ke server (lihat dashboard-app/README.md).
 */

export type Id = string

/** Waktu dalam format ISO 8601, mis. "2026-10-09T08:15:00.000Z" */
export type IsoDateTime = string
/** Tanggal kalender, mis. "2026-10-27" */
export type IsoDate = string
/** Jam 24 jam, mis. "08:00" */
export type Jam = string

export interface Entri {
  id: Id
  dibuat: IsoDateTime
  diperbarui: IsoDateTime
  /** Urutan tampil (kecil = lebih dulu) */
  urutan: number
}

/* ------------------------------------------------------------------ */
/* Alur publikasi: pengumpulan → verifikasi → persetujuan → publikasi  */
/* ------------------------------------------------------------------ */

export type StatusAlur =
  | "draf"
  | "diperiksa"
  | "persetujuan"
  | "terbit"
  | "dikembalikan"
  | "arsip"

export interface Riwayat {
  waktu: IsoDateTime
  oleh: string
  aksi: string
  ke?: StatusAlur
  catatan?: string
}

export interface Beralur {
  status: StatusAlur
  /** Unit penyedia bahan informasi */
  penyedia: string
  /** Pemeriksa yang ditugaskan */
  pemeriksa: string
  catatanRevisi: string
  terbitPada: IsoDateTime | null
  riwayat: Riwayat[]
}

/* ------------------------------------------------------------------ */
/* Berkas                                                              */
/* ------------------------------------------------------------------ */

/** Tempat berkas dipakai; menentukan bucket, format yang diterima, dan siapa yang boleh mengunggah (D-028) */
export type TujuanUnggah = "berita" | "dokumentasi" | "dokumen" | "unit" | "visiMisi" | "profil" | "beranda" | "absensi" | "aplikasi"

export interface Berkas {
  nama: string
  /** ukuran dalam byte */
  ukuran: number
  tipe: string
  /** URL berkas (data URL saat mode lokal, URL server saat terhubung API) */
  url: string
}

/* ------------------------------------------------------------------ */
/* Koleksi                                                             */
/* ------------------------------------------------------------------ */

export type KategoriBerita = "berita" | "pengumuman" | "keliling" | "edukasi"

export interface Berita extends Entri, Beralur {
  judul: string
  slug: string
  kategori: KategoriBerita
  ringkasan: string
  /** Isi tulisan dalam HTML sederhana (paragraf, subjudul, daftar, kutipan, tautan) */
  isi: string
  sampul: string | null
  keteranganSampul: string
  kreditFoto: string
  tanggal: IsoDate
  /** Tampil sebagai tulisan utama di halaman Informasi */
  sorotan: boolean
  /** Tampil di bilah pengumuman & papan layanan beranda */
  pengumumanBeranda: boolean
}

export type KategoriDokumentasi = "keliling" | "sosialisasi" | "operasi" | "internal"

export interface Foto {
  id: Id
  src: string | null
  keterangan: string
}

export interface Dokumentasi extends Entri, Beralur {
  judul: string
  kategori: KategoriDokumentasi
  lokasi: string
  tanggal: IsoDate
  deskripsi: string
  foto: Foto[]
}

export type KategoriDokumen = "regulasi" | "standar" | "formulir" | "laporan"
export type FormatDokumen = "PDF" | "DOCX" | "XLSX" | "JPG" | "Tautan"

export interface Dokumen extends Entri, Beralur {
  judul: string
  keterangan: string
  kategori: KategoriDokumen
  format: FormatDokumen
  nomor: string
  tahun: string
  berkas: Berkas | null
  /** Tautan luar, mis. JDIH */
  tautan: string
}

export interface Persyaratan {
  id: Id
  dokumen: string
  jumlah: string
}

export interface Layanan extends Entri, Beralur {
  nama: string
  slug: string
  ringkasan: string
  loket: string
  jenis: "layanan" | "tambahan"
  cekFisik: boolean
  nonTunai: boolean
  diwakilkan: boolean
  /** Label tambahan, mis. "Meterai Rp10.000" */
  label: string[]
  persyaratan: Persyaratan[]
  catatan: string
  /** Tampil sebagai tab "Layanan utama" di beranda */
  unggulan: boolean
}

/** Siklus tanya jawab di server: draf, terbit, atau ditarik dari portal */
export type StatusFaq = "draft" | "published" | "unpublished"

export interface Faq extends Entri {
  pertanyaan: string
  jawaban: string
  status: StatusFaq
  /** Pernyataan eksplisit bahwa isinya memang untuk warga */
  publik: boolean
  tampil: boolean
}

export type Hari = 0 | 1 | 2 | 3 | 4 | 5 | 6

/** Jenis layanan di luar kantor (D-043) */
export type JenisLayananLuar = "keliling" | "gerai" | "sementara"

export type UnitKeliling = "01" | "02" | "03"

/**
 * Jadwal layanan di luar kantor (D-043): Samsat Keliling pada satu tanggal,
 * gerai tetap pada hari-harinya, atau loket sementara di antara dua tanggal.
 * Nama koleksinya tetap "keliling".
 */
export interface JadwalKeliling extends Entri {
  jenis: JenisLayananLuar
  /** Unit Samsat Keliling; null untuk gerai dan loket */
  unit: UnitKeliling | null
  /** Nama gerai atau loket; kosong untuk Samsat Keliling */
  nama: string
  /** Tanggal pertama (Samsat Keliling: tanggal layanannya); null bila tanpa batas */
  tanggalMulai: IsoDate | null
  /** Tanggal terakhir; null bila tanpa batas */
  tanggalSelesai: IsoDate | null
  /** Hari layanan, 0 = Minggu */
  hari: Hari[]
  mulai: Jam
  selesai: Jam
  lokasi: string
  kecamatan: string
  layanan: string
  catatan: string
  aktif: boolean
}

/** Lokasi yang bisa dipilih saat mengisi jadwal layanan luar kantor (D-044) */
export interface LokasiLayanan extends Entri {
  nama: string
  kecamatan: string
}

export interface PenyesuaianJadwal extends Entri {
  tanggal: IsoDate
  jenis: "tutup" | "jam-khusus"
  mulai: Jam
  selesai: Jam
  keterangan: string
}

export type JenisUnit = "pimpinan" | "tata-usaha" | "seksi" | "fungsional" | "mitra"

export interface UnitKerja extends Entri {
  nama: string
  singkatan: string
  jenis: JenisUnit
  induk: Id | null
  jabatan: string
  pejabat: string
  nip: string
  foto: string | null
  /** Jabatan pelaksana di bawah unit */
  anggota: string[]
  uraianTugas: string
  catatan: string
}

export type JenisAplikasi = "situs" | "android"
export type PengelolaAplikasi = "bpad" | "uptd"

/** Aplikasi atau situs milik BPAD/UPTD di halaman Aplikasi & Situs portal (D-054) */
export interface Aplikasi extends Entri {
  nama: string
  namaLengkap: string
  keterangan: string
  jenis: JenisAplikasi
  pengelola: PengelolaAplikasi
  /** Alamat lengkap (https://…) atau halaman portal (mis. index.html); aplikasi Android ke Google Play */
  tautan: string
  /** Alamat lengkap logo: unggahan, atau logo bawaan daftar awal */
  logo: string | null
}

export type JenisPesan = "pertanyaan" | "masukan" | "pengaduan"
export type StatusPesan = "baru" | "diproses" | "selesai"
export type SumberPesan = "formulir" | "kotak-saran" | "lapor" | "whatsapp" | "telepon"

export interface Tanggapan {
  id: Id
  isi: string
  oleh: string
  waktu: IsoDateTime
}

export interface Pesan extends Entri {
  tiket: string
  jenis: JenisPesan
  nama: string
  kontak: string
  layanan: string
  isi: string
  sumber: SumberPesan
  status: StatusPesan
  diterima: IsoDateTime
  ditangani: string
  tanggapan: Tanggapan[]
}

export interface Halaman extends Entri {
  slug: string
  judul: string
  judulLanjutan: string
  pengantar: string
  isi: string
}

export type ModulPantau =
  | "keliling"
  | "dokumentasi"
  | "jam"
  | "layanan"
  | "berita"
  | "dokumen"
  | "papan"
  | "profil"

export interface Pemutakhiran extends Entri {
  konten: string
  modul: ModulPantau
  unit: string
  frekuensi: string
  /** Batas hari sejak pembaruan terakhir sebelum dianggap jatuh tempo */
  intervalHari: number
}

/* ------------------------------------------------------------------ */
/* Alat bantu internal (tidak tampil di portal publik)                 */
/* ------------------------------------------------------------------ */

export interface Aktivitas {
  id: Id
  waktu: IsoDateTime
  oleh: string
  aksi: string
  modul: string
  target: string
}

export interface Koleksi {
  berita: Berita
  dokumentasi: Dokumentasi
  dokumen: Dokumen
  layanan: Layanan
  faq: Faq
  keliling: JadwalKeliling
  lokasi: LokasiLayanan
  penyesuaian: PenyesuaianJadwal
  unit: UnitKerja
  pesan: Pesan
  halaman: Halaman
  pemutakhiran: Pemutakhiran
  aplikasi: Aplikasi
}

export type NamaKoleksi = keyof Koleksi

/* ------------------------------------------------------------------ */
/* Pengaturan (satu dokumen per halaman)                               */
/* ------------------------------------------------------------------ */

/** Huruf tema (D-057): huruf sistem atau huruf OFL yang di-host sendiri; Manrope dan Lexend khusus judul */
export type HurufTema =
  | "sistem"
  | "inter"
  | "plus-jakarta-sans"
  | "dm-sans"
  | "source-sans-3"
  | "ibm-plex-sans"
  | "nunito-sans"
  | "manrope"
  | "lexend"
export type LatarTema = "hangat" | "netral" | "sejuk"
export type SudutTema = "tegas" | "sedang" | "membulat"
/** Skala tampilan dalam persen: zoom halaman portal, ukuran huruf dasar dashboard */
export type SkalaTema = 90 | 100 | 110 | 120
/** Ketebalan judul: semi-tebal (bawaan), tebal, ekstra tebal; teks tetap 400 */
export type TebalJudul = 600 | 700 | 800

/** Satu bagian tema: portal atau dashboard */
export interface BagianTema {
  /** Warna utama #RRGGBB; turunannya dibuat server dan dijaga kontrasnya */
  aksen: string
  latar: LatarTema
  hurufTeks: Exclude<HurufTema, "manrope" | "lexend">
  /** "sama" = mengikuti huruf teks */
  hurufJudul: HurufTema | "sama"
  sudut: SudutTema
  skala: SkalaTema
  tebalJudul: TebalJudul
}

/** Mode portal untuk semua pengunjung (D-057); mode gelap dashboard tetap pilihan tiap pegawai */
export type ModePortal = "terang" | "gelap"

export interface BagianTemaPortal extends BagianTema {
  mode: ModePortal
}

/** Pengaturan › Tampilan (D-057): tema portal dan dashboard masing-masing */
export interface Tema {
  portal: BagianTemaPortal
  dashboard: BagianTema
}

export interface Situs {
  namaPortal: string
  kepanjangan: string
  namaInstansi: string
  namaSingkat: string
  induk: string
  deskripsi: string
  urlPortal: string
  hakCipta: string
  bilahPengumuman: {
    aktif: boolean
    teks: string
    tautanLabel: string
    tautanUrl: string
  }
}

export interface Judul2Nada {
  judul: string
  lanjutan: string
}

export interface Beranda {
  hero: {
    eyebrow: string
    judul: string
    /** Kata yang bergantian di kata judul yang sama, satu putaran; kosong = judul diam (D-045) */
    kataBergilir: string[]
    lanjutan: string
    deskripsi: string
    tombolLabel: string
    tombolUrl: string
    catatan: string
  }
  /** Video YouTube di balik tombol "Tonton alur pelayanan"; tautan kosong = tombol tidak tampil (D-040) */
  video: { url: string; label: string; durasi: string; judul: string }
  nilai: { id: Id; judul: string; deskripsi: string }[]
  layananUtama: Judul2Nada & { deskripsi: string }
  /** Logo mitra di bucket publik; inisial dipakai bila belum ada logo (D-037) */
  mitra: { id: Id; singkatan: string; nama: string; logo: string | null }[]
  /** Isi kelompok kanal; tautan kosong bila tidak bisa diklik (D-038) */
  kanal: { id: Id; kelompok: string; item: { teks: string; tautan: string }[] }[]
  integritas: Judul2Nada & { deskripsi: string }
  biaya: Judul2Nada & {
    deskripsi: string
    catatanNotis: string
    wajibDibawa: string
    jaminan: string[]
  }
  penutup: Judul2Nada & { deskripsi: string }
}

export type StatusLoket = "buka" | "istirahat" | "tutup"

export interface PapanLayanan {
  nomorAntrean: string
  layananAntrean: string
  waktuTunggu: number
  selisihKemarin: number
  loket: { id: Id; kode: string; nama: string; status: StatusLoket }[]
  catatan: string
  /** Diisi server saat disimpan; null bila belum pernah disimpan */
  diperbarui: IsoDateTime | null
}

export interface JamPelayanan {
  hari: { hari: Hari; buka: boolean; mulai: Jam; selesai: Jam; catatan: string }[]
  batasBerkasMenit: number
  catatan: string
  pemberitahuan: string
}

/** Penerimaan pajak bukan lagi kategori statistik: beranda meringkas Laporan Penerimaan (D-055) */
export type KunciStatistik = "transaksi" | "keliling" | "kepuasan"

export interface Statistik {
  status: "ilustrasi" | "terverifikasi"
  catatan: string
  tahun: string
  set: {
    kunci: KunciStatistik
    label: string
    sub: string
    judulGrafik: string
    satuan: string
    /** Tab kategori ini tampil di dasbor transparansi beranda (D-056) */
    tampil: boolean
    tiles: { id: Id; label: string; nilai: number; satuan: string; perubahan: number; naikBaik: boolean }[]
    /** 12 nilai bulanan, Januari–Desember */
    seri: number[]
  }[]
}

export interface Profil {
  judul: string
  lanjutan: string
  pengantar: string
  foto: string | null
  keteranganFoto: string
  kedudukan: Judul2Nada & { deskripsi: string }
  identitas: { id: Id; label: string; nilai: string }[]
  tugasFungsi: { id: Id; judul: string; deskripsi: string; kataKunci: string }[]
  sambutan: { kutipan: string; nama: string; jabatan: string; foto: string | null }
}

export interface VisiMisi {
  sumberVisi: string
  visi: string
  misi: { id: Id; teks: string; kataKunci: string }[]
  kontribusi: { id: Id; misi: string; judul: string; deskripsi: string }[]
  nilai: { id: Id; huruf: string; judul: string; deskripsi: string }[]
  maklumat: { teks: string; penandatangan: string; berkas: Berkas | null }
}

export interface Kontak {
  namaKantor: string
  alamat: string
  kota: string
  provinsi: string
  telepon: string
  whatsapp: string
  whatsappCatatan: string
  email: string
  mediaSosial: { id: Id; platform: string; akun: string; url: string }[]
  mapsUrl: string
  catatanLokasi: string
  janjiBalasan: string
  kanalPengaduan: { id: Id; nama: string; deskripsi: string; tautanLabel: string; tautanUrl: string }[]
}

export interface Akun {
  nama: string
  jabatan: string
  unit: string
  peran: string
  email: string
}

export interface Pengaturan {
  situs: Situs
  tema: Tema
  beranda: Beranda
  papan: PapanLayanan
  jam: JamPelayanan
  statistik: Statistik
  profil: Profil
  visiMisi: VisiMisi
  kontak: Kontak
  akun: Akun
}

export type NamaPengaturan = keyof Pengaturan

/** Isi entri baru: tanpa kolom yang diisi sistem */
export type Draf<T extends Entri> = Omit<T, "id" | "dibuat" | "diperbarui" | "urutan"> &
  Partial<Pick<T, "urutan">>

export interface Snapshot {
  versi: number
  diekspor: IsoDateTime
  koleksi: { [K in NamaKoleksi]: Koleksi[K][] }
  pengaturan: Pengaturan
  aktivitas: Aktivitas[]
}
