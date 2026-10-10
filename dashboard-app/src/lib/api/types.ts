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

export interface Faq extends Entri {
  pertanyaan: string
  jawaban: string
  tampil: boolean
}

export type Hari = 0 | 1 | 2 | 3 | 4 | 5 | 6

export interface JadwalKeliling extends Entri {
  hari: Hari
  mulai: Jam
  selesai: Jam
  lokasi: string
  kecamatan: string
  layanan: string
  catatan: string
  aktif: boolean
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

export type JenisKegiatan = "keliling" | "operasi" | "sosialisasi" | "lainnya"
export type TipeAbsen = "datang" | "pulang"

export interface Koordinat {
  lat: number
  lng: number
  /** Perkiraan akurasi dalam meter */
  akurasi: number
}

/** Bukti kehadiran petugas pada kegiatan lapangan: foto + titik lokasi */
export interface Absensi extends Entri {
  tipe: TipeAbsen
  kegiatan: string
  jenis: JenisKegiatan
  /** Waktu absen, diisi otomatis saat formulir dikirim */
  waktu: IsoDateTime
  petugas: string
  nip: string
  /** Foto bukti (data URL saat mode lokal, URL server saat terhubung API) */
  foto: string | null
  /** Titik GPS; null bila peramban tidak memberi izin lokasi */
  lokasi: Koordinat | null
  /** Keterangan tempat, mis. "Halaman Kantor Kelurahan Oesapa" */
  tempat: string
  catatan: string
}

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
  penyesuaian: PenyesuaianJadwal
  unit: UnitKerja
  pesan: Pesan
  halaman: Halaman
  pemutakhiran: Pemutakhiran
  absensi: Absensi
}

export type NamaKoleksi = keyof Koleksi

/* ------------------------------------------------------------------ */
/* Pengaturan (satu dokumen per halaman)                               */
/* ------------------------------------------------------------------ */

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
    lanjutan: string
    deskripsi: string
    tombolLabel: string
    tombolUrl: string
    catatan: string
  }
  nilai: { id: Id; judul: string; deskripsi: string }[]
  layananUtama: Judul2Nada & { deskripsi: string }
  mitra: { id: Id; singkatan: string; nama: string }[]
  kanal: { id: Id; kelompok: string; item: string[] }[]
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
  diperbarui: IsoDateTime
}

export interface JamPelayanan {
  hari: { hari: Hari; buka: boolean; mulai: Jam; selesai: Jam; catatan: string }[]
  batasBerkasMenit: number
  catatan: string
  pemberitahuan: string
}

export interface Tarif {
  opsenPersen: number
  dendaPersenPerBulan: number
  maksBulanDenda: number
  swdklljMotor: number
  swdklljMobil: number
  contohPokok: number
  contohJenis: "motor" | "mobil"
  contohTelat: number
  catatan: string
}

export type KunciStatistik = "transaksi" | "penerimaan" | "keliling" | "kepuasan"

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
  beranda: Beranda
  papan: PapanLayanan
  jam: JamPelayanan
  tarif: Tarif
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
