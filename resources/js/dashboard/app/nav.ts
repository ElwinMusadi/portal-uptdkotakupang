import {
  IconAddressBook,
  IconAlarm,
  IconBook2,
  IconBuildingCommunity,
  IconCalculator,
  IconCamera,
  IconCalendarRepeat,
  IconChartBar,
  IconChecklist,
  IconClipboardList,
  IconClockHour4,
  IconDashboard,
  IconFileText,
  IconFiles,
  IconHelpCircle,
  IconHierarchy2,
  IconHome,
  IconInbox,
  IconLayoutBoard,
  IconNews,
  IconPhoto,
  IconSettings,
  IconTargetArrow,
  IconTruck,
  IconUsers,
  type Icon,
} from "@tabler/icons-react"

import type { Modul, Sesi } from "@/lib/auth"

export interface ItemNav {
  judul: string
  url: string
  ikon: Icon
  /** Halaman portal publik yang menampilkan konten ini */
  portal?: string
  /** Rute untuk membuat entri baru (ditampilkan di menu "Buat konten") */
  tambah?: string
  /** Label tindakan tambah di menu samping (default "Tambah baru") */
  labelTambah?: string
  keterangan?: string
  /** Hanya ditampilkan untuk peran ini; server tetap memeriksa haknya */
  khusus?: Sesi["peran"]
  /** Hanya ditampilkan bila pengguna mengelola salah satu modul ini */
  modul?: Modul[]
}

/** Boleh dilihat pengguna dengan peran dan modul tersebut */
export function bolehDilihat(item: Pick<ItemNav, "khusus" | "modul">, sesi: Pick<Sesi, "peran" | "modul">): boolean {
  return (!item.khusus || item.khusus === sesi.peran) && (!item.modul || item.modul.some((m) => sesi.modul.includes(m)))
}

/** Item navigasi yang boleh dilihat pengguna */
export function navUntuk<T extends Pick<ItemNav, "khusus" | "modul">>(items: T[], sesi: Pick<Sesi, "peran" | "modul">): T[] {
  return items.filter((item) => bolehDilihat(item, sesi))
}

export interface GrupNav {
  label: string
  item: ItemNav[]
}

/** Koleksi yang melewati alur publikasi beserta modulnya */
export const MODUL_ALUR: Modul[] = ["berita", "dokumentasi", "dokumen", "layanan"]

export const NAV_UTAMA: ItemNav[] = [
  { judul: "Ringkasan", url: "/", ikon: IconDashboard, keterangan: "Ikhtisar konten, pesan, dan pemutakhiran" },
  { judul: "Alur Publikasi", url: "/alur", ikon: IconLayoutBoard, keterangan: "Draf → diperiksa → persetujuan → terbit", modul: MODUL_ALUR },
  { judul: "Pesan & Pengaduan", url: "/pesan", ikon: IconInbox, portal: "kontak.html#masukan", keterangan: "Pertanyaan, masukan, dan pengaduan warga", modul: ["pesan"] },
  { judul: "Statistik Layanan", url: "/statistik", ikon: IconChartBar, portal: "index.html#transparansi", keterangan: "Dasbor transparansi di beranda", modul: ["statistik"] },
]

export const NAV_GRUP: GrupNav[] = [
  {
    label: "Konten",
    item: [
      { judul: "Berita & Pengumuman", url: "/berita", ikon: IconNews, portal: "informasi.html", tambah: "/berita/baru", modul: ["berita"] },
      { judul: "Dokumentasi Kegiatan", url: "/dokumentasi", ikon: IconPhoto, portal: "dokumentasi.html", tambah: "/dokumentasi?baru=1", modul: ["dokumentasi"] },
      { judul: "Regulasi & Unduhan", url: "/unduhan", ikon: IconFiles, portal: "unduhan.html", tambah: "/unduhan?baru=1", modul: ["dokumen"] },
      { judul: "Tanya Jawab", url: "/faq", ikon: IconHelpCircle, portal: "index.html#faq", tambah: "/faq?baru=1", modul: ["faq"] },
    ],
  },
  {
    label: "Layanan",
    item: [
      { judul: "Persyaratan Layanan", url: "/layanan", ikon: IconChecklist, portal: "layanan.html", tambah: "/layanan/baru", modul: ["layanan"] },
      { judul: "Jam Pelayanan", url: "/jadwal/jam", ikon: IconClockHour4, portal: "jadwal.html", modul: ["jam"] },
      { judul: "Layanan Luar Kantor", url: "/jadwal/keliling", ikon: IconTruck, portal: "jadwal.html#keliling", tambah: "/jadwal/keliling?baru=1", modul: ["keliling"] },
      { judul: "Papan Layanan", url: "/papan", ikon: IconAlarm, portal: "index.html", modul: ["papan"] },
    ],
  },
  {
    label: "Profil Instansi",
    item: [
      { judul: "Tentang UPTD", url: "/profil", ikon: IconBuildingCommunity, portal: "profil.html", modul: ["profil"] },
      { judul: "Visi & Misi", url: "/visi-misi", ikon: IconTargetArrow, portal: "visi-misi.html", modul: ["visiMisi"] },
      { judul: "Struktur Organisasi", url: "/struktur", ikon: IconHierarchy2, portal: "struktur-organisasi.html", tambah: "/struktur?baru=1", modul: ["unit"] },
      { judul: "Kontak & Kanal", url: "/kontak", ikon: IconAddressBook, portal: "kontak.html", modul: ["kontak"] },
    ],
  },
  {
    label: "Situs",
    item: [
      { judul: "Beranda", url: "/beranda", ikon: IconHome, portal: "index.html", modul: ["beranda"] },
      { judul: "Halaman Statis", url: "/halaman", ikon: IconFileText, portal: "kebijakan-privasi.html", modul: ["halaman"] },
      { judul: "Jadwal Pemutakhiran", url: "/pemutakhiran", ikon: IconCalendarRepeat, modul: ["pemutakhiran"] },
    ],
  },
  {
    label: "Alat Bantu",
    item: [
      { judul: "Absensi Lapangan", url: "/absensi", ikon: IconCamera, keterangan: "Clock-in kegiatan lapangan dengan swafoto dan lokasi" },
      { judul: "Kalkulator PKB", url: "/kalkulator", ikon: IconCalculator, keterangan: "Rincian PKB dari nomor polisi, menurut Pergub NTT 54/2026" },
    ],
  },
]

export const NAV_SEKUNDER: ItemNav[] = [
  {
    judul: "Akun Pegawai",
    url: "/akun",
    ikon: IconUsers,
    khusus: "administrator",
    keterangan: "Buat akun, tugaskan modul, terbitkan tautan aktivasi, tangguhkan atau nonaktifkan akun",
  },
  { judul: "Pengaturan", url: "/pengaturan", ikon: IconSettings },
  { judul: "Panduan", url: "/panduan", ikon: IconBook2 },
]

/** Semua item untuk pencarian cepat & judul halaman */
export const SEMUA_NAV: ItemNav[] = [
  ...NAV_UTAMA,
  ...NAV_GRUP.flatMap((g) => g.item),
  ...NAV_SEKUNDER,
]

/** Modul yang bisa ditugaskan, dikelompokkan seperti menu samping */
export const GRUP_MODUL: { label: string; modul: { kunci: Modul; judul: string }[] }[] = [
  { label: "Pesan & statistik", item: NAV_UTAMA },
  ...NAV_GRUP,
]
  .map((grup) => ({
    label: grup.label,
    modul: grup.item.flatMap((i) => (i.modul?.length === 1 ? [{ kunci: i.modul[0], judul: i.judul }] : [])),
  }))
  .filter((grup) => grup.modul.length > 0)

export const BUAT_CEPAT: { judul: string; url: string; ikon: Icon; modul: Modul[] }[] = [
  { judul: "Berita atau pengumuman", url: "/berita/baru", ikon: IconNews, modul: ["berita"] },
  { judul: "Dokumentasi kegiatan", url: "/dokumentasi?baru=1", ikon: IconPhoto, modul: ["dokumentasi"] },
  { judul: "Dokumen unduhan", url: "/unduhan?baru=1", ikon: IconFiles, modul: ["dokumen"] },
  { judul: "Jadwal Samsat Keliling", url: "/jadwal/keliling?baru=1", ikon: IconTruck, modul: ["keliling"] },
  { judul: "Tanya jawab", url: "/faq?baru=1", ikon: IconHelpCircle, modul: ["faq"] },
  { judul: "Catat pesan masuk", url: "/pesan?baru=1", ikon: IconClipboardList, modul: ["pesan"] },
]
