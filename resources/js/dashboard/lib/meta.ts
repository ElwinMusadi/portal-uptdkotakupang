import type {
  Hari,
  JenisKegiatan,
  JenisPesan,
  JenisUnit,
  KategoriBerita,
  KategoriDokumen,
  KategoriDokumentasi,
  Koleksi,
  ModulPantau,
  NamaKoleksi,
  NamaPengaturan,
  StatusAlur,
  StatusLoket,
  StatusPesan,
  SumberPesan,
  TipeAbsen,
} from "@/lib/api/types"

export const KOLEKSI_LABEL: Record<NamaKoleksi, string> = {
  berita: "Berita & Pengumuman",
  dokumentasi: "Dokumentasi Kegiatan",
  dokumen: "Regulasi & Unduhan",
  layanan: "Persyaratan Layanan",
  faq: "Tanya Jawab",
  keliling: "Samsat Keliling",
  penyesuaian: "Penyesuaian Jadwal",
  unit: "Struktur Organisasi",
  pesan: "Pesan & Pengaduan",
  halaman: "Halaman Statis",
  pemutakhiran: "Jadwal Pemutakhiran",
  absensi: "Absensi Lapangan",
}

export const PENGATURAN_LABEL: Record<NamaPengaturan, string> = {
  situs: "Pengaturan Situs",
  beranda: "Beranda",
  papan: "Papan Layanan",
  jam: "Jam Pelayanan",
  tarif: "Tarif & Simulasi",
  statistik: "Statistik Layanan",
  profil: "Tentang UPTD",
  visiMisi: "Visi & Misi",
  kontak: "Kontak & Kanal Pengaduan",
  akun: "Akun",
}

export const HARI_LABEL: Record<Hari, string> = {
  0: "Minggu",
  1: "Senin",
  2: "Selasa",
  3: "Rabu",
  4: "Kamis",
  5: "Jumat",
  6: "Sabtu",
}

/** Judul singkat sebuah entri, dipakai di log aktivitas, dialog, dan pencarian */
export function judulEntri<K extends NamaKoleksi>(koleksi: K, item: Koleksi[K]): string {
  const x = item as unknown as Record<string, unknown>
  switch (koleksi) {
    case "layanan":
    case "unit":
      return String(x.nama ?? "")
    case "faq":
      return String(x.pertanyaan ?? "")
    case "keliling": {
      const k = item as Koleksi["keliling"]
      return `${HARI_LABEL[k.hari]} · ${k.lokasi}`
    }
    case "penyesuaian": {
      const p = item as Koleksi["penyesuaian"]
      return `${p.tanggal} · ${p.keterangan}`
    }
    case "pesan": {
      const p = item as Koleksi["pesan"]
      return `${p.tiket} · ${PESAN_JENIS[p.jenis]}`
    }
    case "pemutakhiran":
      return String(x.konten ?? "")
    case "absensi": {
      const a = item as Koleksi["absensi"]
      return `${TIPE_ABSEN[a.tipe]} · ${a.kegiatan}`
    }
    default:
      return String(x.judul ?? "")
  }
}

/* ------------------------------------------------------------------ */
/* Alur publikasi                                                      */
/* ------------------------------------------------------------------ */

export type Nada = "netral" | "info" | "proses" | "sukses" | "peringatan" | "bahaya"

export const STATUS_ALUR: Record<
  StatusAlur,
  { label: string; peran: string; keterangan: string; nada: Nada }
> = {
  draf: {
    label: "Draf",
    peran: "Penyedia",
    keterangan: "Bahan disiapkan unit pemilik data.",
    nada: "netral",
  },
  diperiksa: {
    label: "Diperiksa",
    peran: "Pemeriksa",
    keterangan: "Dicocokkan dengan sumber resmi dan diperiksa kelengkapannya.",
    nada: "info",
  },
  persetujuan: {
    label: "Menunggu persetujuan",
    peran: "Atasan langsung",
    keterangan: "Menunggu izin terbit dari atasan langsung.",
    nada: "proses",
  },
  terbit: {
    label: "Terbit",
    peran: "Pengelola publikasi",
    keterangan: "Tayang di portal publik.",
    nada: "sukses",
  },
  dikembalikan: {
    label: "Dikembalikan",
    peran: "Penyedia",
    keterangan: "Perlu revisi sesuai catatan pemeriksa atau penyetuju.",
    nada: "peringatan",
  },
  arsip: {
    label: "Diarsipkan",
    peran: "Pengelola publikasi",
    keterangan: "Tidak tayang di portal, tetap tersimpan.",
    nada: "netral",
  },
}

/** Urutan kolom papan alur publikasi */
export const TAHAP_ALUR: StatusAlur[] = ["draf", "diperiksa", "persetujuan", "terbit"]

export interface AksiAlur {
  ke: StatusAlur
  label: string
  /** Kata kerja untuk log aktivitas */
  kerja: string
  perluCatatan?: boolean
  bahaya?: boolean
}

export const AKSI_ALUR: Record<StatusAlur, AksiAlur[]> = {
  draf: [{ ke: "diperiksa", label: "Ajukan pemeriksaan", kerja: "mengajukan pemeriksaan" }],
  diperiksa: [
    { ke: "persetujuan", label: "Teruskan ke penyetuju", kerja: "meneruskan ke penyetuju" },
    { ke: "dikembalikan", label: "Kembalikan", kerja: "mengembalikan", perluCatatan: true, bahaya: true },
  ],
  persetujuan: [
    { ke: "terbit", label: "Setujui & terbitkan", kerja: "menyetujui dan menerbitkan" },
    { ke: "dikembalikan", label: "Kembalikan", kerja: "mengembalikan", perluCatatan: true, bahaya: true },
  ],
  terbit: [{ ke: "arsip", label: "Tarik dari portal", kerja: "menarik dari portal", bahaya: true }],
  dikembalikan: [{ ke: "diperiksa", label: "Ajukan ulang", kerja: "mengajukan ulang" }],
  arsip: [{ ke: "draf", label: "Pulihkan ke draf", kerja: "memulihkan ke draf" }],
}

export function kerjaStatus(ke: StatusAlur): string {
  for (const daftar of Object.values(AKSI_ALUR)) {
    const aksi = daftar.find((a) => a.ke === ke)
    if (aksi) return aksi.kerja
  }
  return "mengubah status"
}

/* ------------------------------------------------------------------ */
/* Pilihan kategori                                                    */
/* ------------------------------------------------------------------ */

export const KATEGORI_BERITA: Record<KategoriBerita, string> = {
  berita: "Berita",
  pengumuman: "Pengumuman",
  keliling: "Samsat Keliling",
  edukasi: "Edukasi",
}

export const KATEGORI_DOKUMENTASI: Record<KategoriDokumentasi, string> = {
  keliling: "Samsat Keliling",
  sosialisasi: "Sosialisasi",
  operasi: "Operasi gabungan",
  internal: "Internal",
}

export const KATEGORI_DOKUMEN: Record<KategoriDokumen, string> = {
  regulasi: "Regulasi",
  standar: "Standar pelayanan",
  formulir: "Formulir",
  laporan: "Laporan",
}

export const JENIS_UNIT: Record<JenisUnit, string> = {
  pimpinan: "Pimpinan",
  "tata-usaha": "Sub Bagian",
  seksi: "Seksi",
  fungsional: "Jabatan fungsional",
  mitra: "Mitra satu atap",
}

export const PESAN_JENIS: Record<JenisPesan, string> = {
  pertanyaan: "Pertanyaan",
  masukan: "Masukan",
  pengaduan: "Pengaduan",
}

export const PESAN_STATUS: Record<StatusPesan, { label: string; nada: Nada }> = {
  baru: { label: "Baru", nada: "info" },
  diproses: { label: "Diproses", nada: "proses" },
  selesai: { label: "Selesai", nada: "sukses" },
}

export const PESAN_SUMBER: Record<SumberPesan, string> = {
  formulir: "Formulir portal",
  "kotak-saran": "Kotak saran",
  lapor: "SP4N-LAPOR!",
  whatsapp: "WhatsApp",
  telepon: "Telepon",
}

export const LAYANAN_PESAN = [
  "Umum / portal PINTU",
  "Pajak tahunan",
  "Perpanjangan STNK 5 tahun",
  "Mutasi & balik nama",
  "Duplikat STNK",
  "Samsat Keliling",
] as const

export const STATUS_LOKET: Record<StatusLoket, { label: string; nada: Nada }> = {
  buka: { label: "Buka", nada: "sukses" },
  istirahat: { label: "Istirahat", nada: "peringatan" },
  tutup: { label: "Tutup", nada: "netral" },
}

export const MODUL_PANTAU: Record<ModulPantau, { label: string; rute: string }> = {
  keliling: { label: "Samsat Keliling", rute: "/jadwal/keliling" },
  dokumentasi: { label: "Dokumentasi Kegiatan", rute: "/dokumentasi" },
  jam: { label: "Jam Pelayanan", rute: "/jadwal/jam" },
  layanan: { label: "Persyaratan Layanan", rute: "/layanan" },
  berita: { label: "Berita & Pengumuman", rute: "/berita" },
  dokumen: { label: "Regulasi & Unduhan", rute: "/unduhan" },
  papan: { label: "Papan Layanan", rute: "/papan" },
  profil: { label: "Tentang UPTD", rute: "/profil" },
}

export const JENIS_KEGIATAN: Record<JenisKegiatan, string> = {
  keliling: "Samsat Keliling",
  operasi: "Operasi gabungan",
  sosialisasi: "Sosialisasi",
  lainnya: "Kegiatan lain",
}

export const TIPE_ABSEN: Record<TipeAbsen, string> = {
  datang: "Datang",
  pulang: "Pulang",
}

export const UNIT_PENYEDIA = [
  "Sub Bagian Tata Usaha",
  "Seksi Pendataan & Penetapan",
  "Seksi Penagihan & Pelaporan",
  "Pengelola publikasi",
] as const

export const PEMERIKSA = [
  "Kepala Sub Bagian Tata Usaha",
  "Kepala Seksi Pendataan & Penetapan",
  "Kepala Seksi Penagihan & Pelaporan",
] as const
