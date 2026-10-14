/*
 * Mesin perhitungan PKB, Opsen PKB, SWDKLLJ, dan PNBP menurut Pergub NTT
 * Nomor 54 Tahun 2026 (D-041), dipakai Kalkulator PKB di dashboard dan cek
 * biaya pajak di portal.
 *
 * lib/tax-calculator.ts dan types/tax.ts adalah salinan persis dari
 * repository kalkulator-pajak (https://github.com/ElwinMusadi/kalkulator-pajak),
 * commit 7e90d89, berkas src/lib/tax-calculator.ts dan src/types/tax.ts.
 * Jangan menyunting kedua berkas itu di sini: rumusnya mengikuti Pergub dan
 * diuji oleh tests/tax-calculator.regression.ts di repository sumber. Bila
 * rumus berubah, salin ulang kedua berkas dari repository sumber dan catat
 * commit-nya di sini. penetapan.ts milik PINTU: label dan ringkasan hasil.
 */
export { calculateTax } from "./lib/tax-calculator"
export * from "./penetapan"
export {
  BOBOT_MAP,
  DEFAULT_WARNA_PLAT,
  isWarnaPlat,
  TARIF_PKB_PLAT_MERAH,
  WARNA_PLAT_LABEL,
  WARNA_PLAT_OPTIONS,
  type JenisKendaraan,
  type TaxCalculationResult,
  type TaxCalculatorInput,
  type WarnaPlat,
} from "./types/tax"

/** Data kendaraan dari GET /api/kendaraan/{nopol} dan POST /api/public/kendaraan */
export interface DataKendaraan {
  nopol: string
  nama: string | null
  /** Klasifikasi BPAD mentah, mis. "MINIBUS"; null bila tidak ada */
  jenis: string | null
  jatuhTempoStnk: string | null
  jatuhTempoPajak: string | null
  njkb: number
  njub: number
  bobot: number
  warnaPlat: "putih" | "hitam" | "merah" | null
  /** "not_found": kendaraan ada, tetapi referensi NJKB-nya tidak (NJKB 0, isi manual) */
  njkbStatus: "matched" | "not_found"
}
