/*
 * Periode Laporan Penerimaan (harian, bulanan, atau rentang), dipakai halaman
 * portal (D-048) dan dashboard (D-047). Tanggal ditulis "YYYY-MM-DD" dan
 * dihitung sebagai tanggal kalender, tanpa jam dan zona waktu.
 */

export type Mode = "harian" | "bulanan" | "rentang"

export interface Periode {
  mode: Mode
  dari: string
  sampai: string
}

/** Rentang laporan terpanjang yang diterima server, dalam hari */
export const MAKS_HARI = 366

export const TANGGAL_ISO = /^\d{4}-\d{2}-\d{2}$/

const iso = (d: Date) => d.toISOString().slice(0, 10)
const keDate = (t: string) => new Date(`${t}T00:00:00Z`)

export function geserHari(t: string, n: number): string {
  const [y, m, d] = t.split("-").map(Number)
  return iso(new Date(Date.UTC(y, m - 1, d + n)))
}

/** Hari terakhir bulan "YYYY-MM" */
export function akhirBulan(bulan: string): string {
  const [y, m] = bulan.split("-").map(Number)
  return iso(new Date(Date.UTC(y, m, 0)))
}

function geserBulan(bulan: string, n: number): string {
  const [y, m] = bulan.split("-").map(Number)
  return iso(new Date(Date.UTC(y, m - 1 + n, 1))).slice(0, 7)
}

/** Banyaknya hari dari tanggal pertama sampai tanggal kedua, keduanya terhitung */
export function jumlahHari(dari: string, sampai: string): number {
  return Math.round((keDate(sampai).getTime() - keDate(dari).getTime()) / 86400000) + 1
}

const bulanPenuh = (dari: string, sampai: string) => dari.endsWith("-01") && sampai === akhirBulan(dari.slice(0, 7))

export function periodeBulan(bulan: string): Periode {
  return { mode: "bulanan", dari: `${bulan}-01`, sampai: akhirBulan(bulan) }
}

/** Periode yang paling cocok untuk rentang tanggal, mis. dari sebuah impor */
export function periodeDari(dari: string, sampai: string): Periode {
  if (dari === sampai) return { mode: "harian", dari, sampai }
  if (bulanPenuh(dari, sampai)) return { mode: "bulanan", dari, sampai }
  return { mode: "rentang", dari, sampai }
}

/** Periode jenis lain di sekitar periode ini; hari tidak melewati batas akhir */
export function gantiMode(p: Periode, mode: Mode, batasAkhir: string): Periode {
  if (mode === "harian") {
    const hari = p.sampai > batasAkhir ? batasAkhir : p.sampai
    return { mode, dari: hari, sampai: hari }
  }
  if (mode === "bulanan") return periodeBulan(p.sampai.slice(0, 7))
  return p.dari === p.sampai ? { mode, dari: geserHari(p.sampai, -6), sampai: p.sampai } : { ...p, mode }
}

/** Hari atau bulan sebelum (n = -1) atau sesudah (n = 1) periode ini */
export function geserPeriode(p: Periode, n: number): Periode {
  if (p.mode === "bulanan") return periodeBulan(geserBulan(p.dari.slice(0, 7), n))
  const hari = geserHari(p.dari, n)
  return { mode: "harian", dari: hari, sampai: hari }
}

const fmt = (opsi: Intl.DateTimeFormatOptions) => new Intl.DateTimeFormat("id-ID", { ...opsi, timeZone: "UTC" })
const fmtLengkap = fmt({ weekday: "long", day: "numeric", month: "long", year: "numeric" })
const fmtPendek = fmt({ day: "numeric", month: "short", year: "numeric" })
const fmtBulan = fmt({ month: "long", year: "numeric" })

/** "Rabu, 14 Oktober 2026" atau "14 Okt 2026" */
export function labelTanggal(t: string, gaya: "lengkap" | "pendek" = "pendek"): string {
  return (gaya === "lengkap" ? fmtLengkap : fmtPendek).format(keDate(t))
}

/** "Rabu, 14 Oktober 2026", "September 2026", atau "1 Sep 2026 – 14 Okt 2026" */
export function labelPeriode(dari: string, sampai: string): string {
  if (dari === sampai) return labelTanggal(dari, "lengkap")
  if (bulanPenuh(dari, sampai)) return fmtBulan.format(keDate(dari))
  return `${labelTanggal(dari)} – ${labelTanggal(sampai)}`
}

export function galatPeriode(p: Periode): string | null {
  if (!TANGGAL_ISO.test(p.dari) || !TANGGAL_ISO.test(p.sampai)) return "Pilih tanggal laporan."
  if (p.sampai < p.dari) return "Tanggal akhir tidak boleh sebelum tanggal awal."
  if (jumlahHari(p.dari, p.sampai) > MAKS_HARI) return "Rentang laporan paling lama satu tahun."
  return null
}
