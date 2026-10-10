import type { Beralur, StatusAlur } from "@/lib/api"
import type { AksiAlur } from "@/lib/meta"

export type KoleksiBeralur = "berita" | "dokumentasi" | "dokumen" | "layanan"

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
