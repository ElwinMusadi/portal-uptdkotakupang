import type { BilahPengumuman } from "@/lib/api"

/**
 * Bilah pengumuman sebelum pertama kali disimpan (D-082): sama dengan bilah di
 * HTML halaman Informasi dan Setting::INITIAL_ANNOUNCEMENT_BAR di server,
 * ditulis sendiri dan tidak tampil di Beranda (D-083).
 */
export const BILAH_PENGUMUMAN_BAWAAN: BilahPengumuman = {
  aktif: true,
  beranda: false,
  sumber: "teks",
  tulisan: null,
  teks: "Program pemutihan denda PKB diperpanjang hingga 31 Desember 2026.",
  tautanLabel: "Baca",
  tautanUrl: "berita-detail.html",
}
