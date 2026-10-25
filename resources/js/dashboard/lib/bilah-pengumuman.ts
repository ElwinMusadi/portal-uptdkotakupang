import type { BilahPengumuman } from "@/lib/api"

/**
 * Bilah pengumuman sebelum pertama kali disimpan (D-082): sama dengan bilah di
 * HTML halaman Informasi dan Setting::INITIAL_ANNOUNCEMENT_BAR di server.
 */
export const BILAH_PENGUMUMAN_BAWAAN: BilahPengumuman = {
  aktif: true,
  teks: "Program pemutihan denda PKB diperpanjang hingga 31 Desember 2026.",
  tautanLabel: "Baca",
  tautanUrl: "berita-detail.html",
}
