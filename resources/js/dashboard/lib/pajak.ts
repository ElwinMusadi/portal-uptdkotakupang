import type { Tarif } from "@/lib/api"

export type JenisKendaraan = Tarif["contohJenis"]

export interface MasukanPkb {
  pokok: number
  jenis: JenisKendaraan
  /** Bulan keterlambatan */
  telat: number
  /** Masa amnesti (pemutihan): denda keterlambatan dihapus */
  amnesti?: boolean
}

export interface HasilPkb {
  pokok: number
  opsen: number
  /** Denda yang ditagih (0 saat amnesti) */
  denda: number
  /** Denda sebelum amnesti, untuk menunjukkan besar potongan */
  dendaAsli: number
  swd: number
  total: number
  /** Bulan yang dikenai denda (dibatasi maksBulanDenda) */
  bulan: number
}

export const LABEL_KENDARAAN: Record<JenisKendaraan, string> = {
  motor: "Sepeda motor",
  mobil: "Mobil",
}

/** Rumus yang sama dengan simulasi di portal (assets/js/main.js) */
export function hitungPkb(t: Tarif, m: MasukanPkb): HasilPkb {
  const n = (x: number) => (Number.isFinite(x) ? x : 0)
  const pokok = Math.max(0, n(m.pokok))
  const opsen = pokok * (n(t.opsenPersen) / 100)
  const bulan = Math.max(0, Math.min(n(m.telat), n(t.maksBulanDenda)))
  const dendaAsli = (pokok + opsen) * (n(t.dendaPersenPerBulan) / 100) * bulan
  const denda = m.amnesti ? 0 : dendaAsli
  const swd = m.jenis === "mobil" ? n(t.swdklljMobil) : n(t.swdklljMotor)
  return { pokok, opsen, denda, dendaAsli, swd, total: pokok + opsen + denda + swd, bulan }
}
