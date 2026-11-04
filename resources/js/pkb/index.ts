/*
 * Mesin perhitungan PKB, Opsen PKB, SWDKLLJ, dan PNBP menurut Pergub NTT
 * keringanan PKB yang berlaku pada tanggal bayar (D-041, D-088): Nomor 64
 * Tahun 2026 untuk 1 November–31 Desember 2026, Nomor 54 Tahun 2026 untuk
 * 1 September–31 Oktober 2026. Dipakai Kalkulator PKB di dashboard dan cek
 * biaya pajak di portal.
 *
 * lib/tax-calculator.ts, lib/pergub.ts, lib/swdkllj.ts, dan types/tax.ts adalah
 * salinan persis dari repository kalkulator-pajak
 * (https://github.com/ElwinMusadi/kalkulator-pajak), commit 405e0ee, berkas
 * src/lib/tax-calculator.ts, src/lib/pergub.ts, src/lib/swdkllj.ts, dan
 * src/types/tax.ts. Jangan menyunting keempat berkas itu di sini: rumusnya
 * mengikuti Pergub dan tarif Jasa Raharja, dan diuji oleh
 * tests/tax-calculator.regression.ts di repository sumber. Bila rumus berubah,
 * salin ulang keempat berkas dari repository sumber dan catat commit-nya di
 * sini. penetapan.ts milik PINTU: label dan ringkasan hasil.
 *
 * SWDKLLJ dihitung per golongan Jasa Raharja (D-089); golongan sepeda motor
 * dan mobil barang bergantung isi silinder (CC).
 */
export { calculateTax } from "./lib/tax-calculator"
export {
  formatMasaBerlaku,
  getPergub,
  hasWilayahGempa,
  PERGUB_RULES,
  PERGUB_TERBARU,
  resolvePergub,
  type PergubRule,
} from "./lib/pergub"
export { bergantungCc, golonganSwdkllj, TARIF_SWDKLLJ, type TarifSwdkllj } from "./lib/swdkllj"
export * from "./penetapan"
export {
  BOBOT_MAP,
  DEFAULT_WARNA_PLAT,
  isWarnaPlat,
  TARIF_PKB_PLAT_MERAH,
  WARNA_PLAT_LABEL,
  WARNA_PLAT_OPTIONS,
  type GolonganSwdkllj,
  type JenisKendaraan,
  type PergubId,
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
  /** Isi silinder (cc) dari vehicle.engine_capacity_cc; null bila tidak tersedia (D-089) */
  isiSilinder: number | null
  warnaPlat: "putih" | "hitam" | "merah" | null
  /** "not_found": kendaraan ada, tetapi referensi NJKB-nya tidak (NJKB 0, isi manual) */
  njkbStatus: "matched" | "not_found"
}
