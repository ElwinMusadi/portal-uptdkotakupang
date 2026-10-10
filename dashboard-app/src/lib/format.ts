/* Format tanggal, angka, dan teks untuk bahasa Indonesia (zona waktu WITA). */

export const ZONA_WAKTU = "Asia/Makassar"

const angkaFmt = new Intl.NumberFormat("id-ID")
const relatifFmt = new Intl.RelativeTimeFormat("id-ID", { numeric: "auto" })

/** Tanggal kalender ("2026-10-27") dibaca sebagai tanggal lokal, bukan UTC */
function keDate(nilai: string | Date): Date {
  if (nilai instanceof Date) return nilai
  if (/^\d{4}-\d{2}-\d{2}$/.test(nilai)) {
    const [y, m, d] = nilai.split("-").map(Number)
    return new Date(Date.UTC(y, m - 1, d, 4)) // 12.00 WITA
  }
  return new Date(nilai)
}

export function tanggal(
  nilai: string | Date | null | undefined,
  gaya: "pendek" | "panjang" | "lengkap" = "pendek"
): string {
  if (!nilai) return "—"
  const d = keDate(nilai)
  if (Number.isNaN(d.getTime())) return "—"
  const opsi: Intl.DateTimeFormatOptions =
    gaya === "pendek"
      ? { day: "numeric", month: "short", year: "numeric" }
      : gaya === "panjang"
        ? { day: "numeric", month: "long", year: "numeric" }
        : { weekday: "long", day: "numeric", month: "long", year: "numeric" }
  return new Intl.DateTimeFormat("id-ID", { ...opsi, timeZone: ZONA_WAKTU }).format(d)
}

export function tanggalJam(nilai: string | Date | null | undefined): string {
  if (!nilai) return "—"
  const d = keDate(nilai)
  if (Number.isNaN(d.getTime())) return "—"
  const tgl = new Intl.DateTimeFormat("id-ID", {
    day: "numeric",
    month: "short",
    year: "numeric",
    timeZone: ZONA_WAKTU,
  }).format(d)
  const jam = new Intl.DateTimeFormat("id-ID", {
    hour: "2-digit",
    minute: "2-digit",
    hourCycle: "h23",
    timeZone: ZONA_WAKTU,
  })
    .format(d)
    .replace(":", ".")
  return `${tgl}, ${jam} WITA`
}

export function waktuRelatif(nilai: string | Date | null | undefined, sekarang = new Date()): string {
  if (!nilai) return "—"
  const d = keDate(nilai)
  const detik = Math.round((d.getTime() - sekarang.getTime()) / 1000)
  const abs = Math.abs(detik)
  if (abs < 60) return "baru saja"
  if (abs < 3600) return relatifFmt.format(Math.round(detik / 60), "minute")
  if (abs < 86400) return relatifFmt.format(Math.round(detik / 3600), "hour")
  if (abs < 86400 * 30) return relatifFmt.format(Math.round(detik / 86400), "day")
  return tanggal(d)
}

/** Selisih hari kalender (WITA) antara dua waktu */
export function selisihHari(dari: string | Date, ke: string | Date = new Date()): number {
  const a = hariIniIso(keDate(dari))
  const b = hariIniIso(keDate(ke))
  const [ay, am, ad] = a.split("-").map(Number)
  const [by, bm, bd] = b.split("-").map(Number)
  return Math.round((Date.UTC(by, bm - 1, bd) - Date.UTC(ay, am - 1, ad)) / 86400000)
}

/** Tanggal hari ini (WITA) dalam format "YYYY-MM-DD" */
export function hariIniIso(d = new Date()): string {
  const parts = new Intl.DateTimeFormat("en-CA", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    timeZone: ZONA_WAKTU,
  }).format(d)
  return parts
}

export function angka(n: number | null | undefined, desimal = 0): string {
  if (n === null || n === undefined || Number.isNaN(n)) return "—"
  return new Intl.NumberFormat("id-ID", {
    minimumFractionDigits: desimal,
    maximumFractionDigits: desimal,
  }).format(n)
}

export function rupiah(n: number): string {
  return "Rp" + angkaFmt.format(Math.round(n))
}

export function ukuranBerkas(byte: number): string {
  if (!byte) return "0 KB"
  if (byte < 1024 * 1024) return `${angka(Math.max(1, Math.round(byte / 1024)))} KB`
  return `${angka(byte / (1024 * 1024), 1)} MB`
}

export function slugify(teks: string): string {
  return teks
    .normalize("NFKD")
    .replace(/[̀-ͯ]/g, "")
    .toLowerCase()
    .replace(/&/g, " dan ")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "")
    .slice(0, 80)
}

/** "08:00" → "08.00" (gaya penulisan jam di portal) */
export function jamTitik(jam: string): string {
  return jam.replace(":", ".")
}

export function inisial(nama: string): string {
  const kata = nama
    .replace(/[^\p{L}\s]/gu, " ")
    .trim()
    .split(/\s+/)
    .filter(Boolean)
  if (kata.length === 0) return "?"
  if (kata.length === 1) return kata[0].slice(0, 2).toUpperCase()
  return (kata[0][0] + kata[1][0]).toUpperCase()
}

/** Teks polos dari HTML (untuk hitung kata / pratinjau). DOMParser tidak menjalankan skrip atau memuat gambar. */
export function teksPolos(html: string): string {
  const doc = new DOMParser().parseFromString(html, "text/html")
  return (doc.body.textContent ?? "").replace(/\s+/g, " ").trim()
}

export function menitBaca(html: string): number {
  const kata = teksPolos(html).split(" ").filter(Boolean).length
  return Math.max(1, Math.round(kata / 200))
}
