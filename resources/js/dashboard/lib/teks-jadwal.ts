import type { TeksJadwal } from "@/lib/api"

/**
 * Teks pembuka halaman Jadwal sebelum pertama kali disimpan (D-080): sama dengan
 * teks di HTML halaman dan Setting::INITIAL_SCHEDULE_PAGE di server.
 */
export const TEKS_JADWAL_BAWAAN: TeksJadwal = {
  pembuka: {
    eyebrow: "Jadwal pelayanan",
    judul: "Buka setiap hari kerja.",
    lanjutan: "Cek dulu sebelum berangkat.",
    deskripsi:
      "Status loket di bawah mengikuti waktu Kupang (WITA) dan berubah otomatis. Hari libur nasional mengikuti ketetapan pemerintah.",
  },
  jam: {
    eyebrow: "Jam loket kantor",
    judul: "Datang pagi.",
    lanjutan: "Antre lebih pendek.",
  },
  luarKantor: {
    eyebrow: "Layanan di luar kantor",
    judul: "Kami yang datang.",
    lanjutan: "Ke kelurahan Anda.",
    deskripsi:
      "Samsat Keliling 01, 02, dan 03 bergiliran mendatangi kelurahan dan keramaian warga. Ada juga gerai tetap dan loket sementara di dekat Anda. Bawa STNK, notis, dan KTP asli. Jadwal minggu depan terbit setiap Jumat.",
    catatan: "Samsat Keliling hanya melayani pajak tahunan.",
  },
}
