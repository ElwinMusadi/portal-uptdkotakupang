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
  IconReceiptTax,
  IconSettings,
  IconTargetArrow,
  IconTruck,
  type Icon,
} from "@tabler/icons-react"

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
}

export interface GrupNav {
  label: string
  item: ItemNav[]
}

export const NAV_UTAMA: ItemNav[] = [
  { judul: "Ringkasan", url: "/", ikon: IconDashboard, keterangan: "Ikhtisar konten, pesan, dan pemutakhiran" },
  { judul: "Alur Publikasi", url: "/alur", ikon: IconLayoutBoard, keterangan: "Draf → diperiksa → persetujuan → terbit" },
  { judul: "Pesan & Pengaduan", url: "/pesan", ikon: IconInbox, portal: "kontak.html#masukan", keterangan: "Pertanyaan, masukan, dan pengaduan warga" },
  { judul: "Statistik Layanan", url: "/statistik", ikon: IconChartBar, portal: "index.html#transparansi", keterangan: "Dasbor transparansi di beranda" },
]

export const NAV_GRUP: GrupNav[] = [
  {
    label: "Konten",
    item: [
      { judul: "Berita & Pengumuman", url: "/berita", ikon: IconNews, portal: "informasi.html", tambah: "/berita/baru" },
      { judul: "Dokumentasi Kegiatan", url: "/dokumentasi", ikon: IconPhoto, portal: "dokumentasi.html", tambah: "/dokumentasi?baru=1" },
      { judul: "Regulasi & Unduhan", url: "/unduhan", ikon: IconFiles, portal: "unduhan.html", tambah: "/unduhan?baru=1" },
      { judul: "Tanya Jawab", url: "/faq", ikon: IconHelpCircle, portal: "index.html#faq", tambah: "/faq?baru=1" },
    ],
  },
  {
    label: "Layanan",
    item: [
      { judul: "Persyaratan Layanan", url: "/layanan", ikon: IconChecklist, portal: "layanan.html", tambah: "/layanan/baru" },
      { judul: "Jam Pelayanan", url: "/jadwal/jam", ikon: IconClockHour4, portal: "jadwal.html" },
      { judul: "Samsat Keliling", url: "/jadwal/keliling", ikon: IconTruck, portal: "jadwal.html#keliling", tambah: "/jadwal/keliling?baru=1" },
      { judul: "Papan Layanan", url: "/papan", ikon: IconAlarm, portal: "index.html" },
      { judul: "Tarif & Simulasi", url: "/tarif", ikon: IconReceiptTax, portal: "layanan.html#simulasi" },
    ],
  },
  {
    label: "Profil Instansi",
    item: [
      { judul: "Tentang UPTD", url: "/profil", ikon: IconBuildingCommunity, portal: "profil.html" },
      { judul: "Visi & Misi", url: "/visi-misi", ikon: IconTargetArrow, portal: "visi-misi.html" },
      { judul: "Struktur Organisasi", url: "/struktur", ikon: IconHierarchy2, portal: "struktur-organisasi.html", tambah: "/struktur?baru=1" },
      { judul: "Kontak & Kanal", url: "/kontak", ikon: IconAddressBook, portal: "kontak.html" },
    ],
  },
  {
    label: "Situs",
    item: [
      { judul: "Beranda", url: "/beranda", ikon: IconHome, portal: "index.html" },
      { judul: "Halaman Statis", url: "/halaman", ikon: IconFileText, portal: "kebijakan-privasi.html" },
      { judul: "Jadwal Pemutakhiran", url: "/pemutakhiran", ikon: IconCalendarRepeat },
    ],
  },
  {
    label: "Alat Bantu",
    item: [
      { judul: "Absensi Lapangan", url: "/absensi", ikon: IconCamera, tambah: "/absensi?baru=1", labelTambah: "Absen sekarang", keterangan: "Foto dan lokasi kehadiran di kegiatan lapangan" },
      { judul: "Kalkulator PKB", url: "/kalkulator", ikon: IconCalculator, keterangan: "Estimasi tagihan untuk petugas loket, termasuk amnesti" },
    ],
  },
]

export const NAV_SEKUNDER: ItemNav[] = [
  { judul: "Pengaturan", url: "/pengaturan", ikon: IconSettings },
  { judul: "Panduan", url: "/panduan", ikon: IconBook2 },
]

/** Semua item untuk pencarian cepat & judul halaman */
export const SEMUA_NAV: ItemNav[] = [
  ...NAV_UTAMA,
  ...NAV_GRUP.flatMap((g) => g.item),
  ...NAV_SEKUNDER,
]

export const BUAT_CEPAT: { judul: string; url: string; ikon: Icon }[] = [
  { judul: "Berita atau pengumuman", url: "/berita/baru", ikon: IconNews },
  { judul: "Dokumentasi kegiatan", url: "/dokumentasi?baru=1", ikon: IconPhoto },
  { judul: "Dokumen unduhan", url: "/unduhan?baru=1", ikon: IconFiles },
  { judul: "Jadwal Samsat Keliling", url: "/jadwal/keliling?baru=1", ikon: IconTruck },
  { judul: "Tanya jawab", url: "/faq?baru=1", ikon: IconHelpCircle },
  { judul: "Catat pesan masuk", url: "/pesan?baru=1", ikon: IconClipboardList },
]
