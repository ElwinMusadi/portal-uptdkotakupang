import { diServer, type Beralur, type StatusAlur } from "@/lib/api"
import { sesi } from "@/lib/auth"
import type { AksiAlur } from "@/lib/meta"

export type KoleksiBeralur = "berita" | "dokumentasi" | "dokumen" | "layanan"

/**
 * Entri yang menunggu persetujuan atau sudah terbit hanya bisa diubah
 * Administrator, termasuk menyetujui, mengembalikan dari tahap persetujuan,
 * dan menarik dari portal. Server menegakkannya untuk koleksi yang sudah
 * tersimpan di sana (D-022); untuk koleksi di peramban semua langkah tetap
 * terbuka.
 */
export function bolehUbahAlur(koleksi: KoleksiBeralur, status: StatusAlur): boolean {
  return !diServer(koleksi) || sesi().peran === "administrator" || (status !== "persetujuan" && status !== "terbit")
}

/** Urutan dan penghapusan entri di server hanya untuk Administrator (D-022) */
export function khususAdministrator(koleksi: KoleksiBeralur): boolean {
  return diServer(koleksi) && sesi().peran !== "administrator"
}

/** Perubahan kolom alur ketika sebuah aksi dijalankan */
export function patchAlur(item: Beralur, aksi: AksiAlur, oleh: string, catatan?: string): Partial<Beralur> {
  const kini = new Date().toISOString()
  return {
    status: aksi.ke,
    catatanRevisi:
      aksi.ke === "dikembalikan" ? (catatan ?? "") : aksi.ke === "diperiksa" ? "" : item.catatanRevisi,
    terbitPada: aksi.ke === "terbit" ? kini : item.terbitPada,
    riwayat: [
      { waktu: kini, oleh, aksi: aksi.kerja, ke: aksi.ke, ...(catatan ? { catatan } : {}) },
      ...item.riwayat,
    ],
  }
}

/** Kolom alur untuk entri baru */
export function alurBaru(oleh: string, penyedia: string, pemeriksa: string): Beralur {
  return {
    status: "draf" as StatusAlur,
    penyedia,
    pemeriksa,
    catatanRevisi: "",
    terbitPada: null,
    riwayat: [{ waktu: new Date().toISOString(), oleh, aksi: "menyiapkan draf", ke: "draf" }],
  }
}
