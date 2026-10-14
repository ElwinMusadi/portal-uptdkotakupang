/*
 * Tampilan hasil mesin pajak yang dipakai bersama oleh Kalkulator PKB di
 * dashboard dan cek biaya pajak di portal (D-041): jenis kendaraan, tanggal,
 * label rincian, dan ringkasan yang disalin. Labelnya mengikuti aplikasi
 * kalkulator-pajak (src/components/result-breakdown.tsx, result-summary.tsx
 * dan tax-calculator-form.tsx). Berkas ini milik PINTU, bukan salinan.
 */
import {
  BOBOT_MAP,
  DEFAULT_WARNA_PLAT,
  isWarnaPlat,
  WARNA_PLAT_LABEL,
  type JenisKendaraan,
  type TaxCalculationResult,
  type WarnaPlat,
} from "./types/tax"

export const ZONA_WAKTU = "Asia/Makassar"

/** Pilihan jenis kendaraan beserta bobotnya, urutannya sama dengan kalkulator-pajak */
export const JENIS_KENDARAAN: readonly { value: JenisKendaraan; label: string }[] = [
  { value: "SEPEDA MOTOR", label: "Sepeda Motor · 1,00" },
  { value: "MINIBUS", label: "Minibus · 1,05" },
  { value: "PICK UP", label: "Pick Up · 1,085" },
  { value: "SEDAN", label: "Sedan · 1,025" },
  { value: "JEEP", label: "Jeep · 1,05" },
  { value: "LIGHT TRUCK", label: "Light Truck · 1,30" },
  { value: "MICROBUS", label: "Microbus · 1,085" },
  { value: "TRUCK", label: "Truck · 1,40" },
]

const angkaFmt = new Intl.NumberFormat("id-ID")
const persenFmt = new Intl.NumberFormat("id-ID", { style: "percent", maximumFractionDigits: 2 })

export const rupiah = (n: number) => "Rp" + angkaFmt.format(Math.round(n))

/** Rasio sebagai persen: 0,005 → "0,5%", 0,15 → "15%" */
export const persen = (rasio: number) => persenFmt.format(rasio)

/** Jenis kendaraan bila klasifikasi BPAD sama persis dengan salah satu jenis mesin pajak */
export function jenisDariData(raw: string | null | undefined): JenisKendaraan | undefined {
  const jenis = raw?.trim().toUpperCase()
  return jenis && jenis in BOBOT_MAP ? (jenis as JenisKendaraan) : undefined
}

/** Warna plat dari data; kosong atau tidak dikenal menjadi Putih */
export function warnaPlatDariData(raw: unknown): WarnaPlat {
  return isWarnaPlat(raw) ? raw : DEFAULT_WARNA_PLAT
}

/** "2026-03-01" sebagai tengah malam waktu setempat, cara mesin pajak membaca tanggal */
export function keTanggal(iso: string | null | undefined): Date | undefined {
  const m = iso ? /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso) : null
  if (!m) return undefined
  const d = new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]))
  return d.getMonth() === Number(m[2]) - 1 ? d : undefined
}

export function dariTanggal(d: Date): string {
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`
}

/** Tanggal hari ini di Kupang (WITA) sebagai YYYY-MM-DD */
export function hariIni(): string {
  return new Intl.DateTimeFormat("en-CA", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    timeZone: ZONA_WAKTU,
  }).format(new Date())
}

/** "1 Maret 2026" dari YYYY-MM-DD */
export function tanggalPanjang(iso: string): string {
  const d = keTanggal(iso)
  return d ? new Intl.DateTimeFormat("id-ID", { day: "numeric", month: "long", year: "numeric" }).format(d) : "—"
}

/**
 * Mesin pajak menghitung masa berjalan pada tahun pembayaran. Bila jatuh tempo
 * pajak berada di tahun sesudahnya, PKB tahun pembayaran sudah lunas dan
 * rinciannya belum bisa dihitung.
 */
export function jatuhTempoSesudahTahunBayar(jatuhTempoPajak: Date, tanggalBayar: Date): boolean {
  return jatuhTempoPajak.getFullYear() > tanggalBayar.getFullYear()
}

export interface BarisRincian {
  label: string
  nilai: number
  /** Denda yang dibebaskan (tax amnesty) */
  dibebaskan?: boolean
}

export interface KelompokRincian {
  judul: string
  jumlah: number
  baris: BarisRincian[]
}

/** Rincian pembayaran per komponen, dengan label yang sama di dashboard dan portal */
export function rincianPembayaran(h: TaxCalculationResult): KelompokRincian[] {
  const kelompok: KelompokRincian[] = [
    {
      judul: "PKB",
      jumlah: h.pkbBerjalan + h.pkbTunggakanPra2025 + h.pkbTunggakanPost2025,
      baris: [
        {
          label: h.pkbDiscount > 0 ? `PKB berjalan · diskon ${persen(h.pkbDiscount)}` : "PKB berjalan",
          nilai: h.pkbBerjalan,
        },
        ...(h.pkbTunggakanPost2025 > 0
          ? [{ label: `Tunggakan ≥ 5 Jan 2025 · ${h.tahunTunggakanPost} tahun`, nilai: h.pkbTunggakanPost2025 }]
          : []),
        ...(h.pkbTunggakanPra2025 > 0
          ? [{ label: `Tunggakan < 5 Jan 2025 · ${h.tahunTunggakanPra} tahun`, nilai: h.pkbTunggakanPra2025 }]
          : []),
        { label: "Denda PKB · dibebaskan 100%", nilai: h.dendaPkb, dibebaskan: true },
      ],
    },
    {
      judul: "Opsen PKB",
      jumlah: h.opsenBerjalan + h.opsenTunggakan,
      baris: [
        { label: "Opsen berjalan", nilai: h.opsenBerjalan },
        ...(h.opsenTunggakan > 0
          ? [{ label: `Opsen tunggakan · ${h.tahunTunggakanPost} tahun`, nilai: h.opsenTunggakan }]
          : []),
        { label: "Denda Opsen · dibebaskan 100%", nilai: h.dendaOpsen, dibebaskan: true },
      ],
    },
    {
      judul: "SWDKLLJ",
      jumlah: h.swdklljBerjalan + h.swdklljTunggakan + h.dendaSwdkllj,
      baris: [
        { label: "SWDKLLJ berjalan", nilai: h.swdklljBerjalan },
        ...(h.swdklljTunggakan > 0
          ? [{ label: `SWDKLLJ tunggakan · ${h.tahunTunggakan} tahun`, nilai: h.swdklljTunggakan }]
          : []),
        ...(h.dendaSwdkllj > 0
          ? [{ label: `Denda SWDKLLJ · ${h.bulanTerlambat} bulan`, nilai: h.dendaSwdkllj }]
          : []),
      ],
    },
  ]
  if (h.biayaStnk > 0 || h.biayaTnkb > 0) {
    kelompok.push({
      judul: "STNK & TNKB",
      jumlah: h.biayaStnk + h.biayaTnkb,
      baris: [
        ...(h.biayaStnk > 0 ? [{ label: "PNBP STNK", nilai: h.biayaStnk }] : []),
        ...(h.biayaTnkb > 0 ? [{ label: "PNBP TNKB", nilai: h.biayaTnkb }] : []),
      ],
    })
  }
  return kelompok
}

/** "2 tahun tunggakan · 1 tahun berjalan" */
export function keteranganMasa(h: TaxCalculationResult): string {
  return h.tahunTunggakan > 0
    ? `${h.tahunTunggakan} tahun tunggakan · 1 tahun berjalan`
    : "Tidak ada tunggakan · bayar tahun berjalan"
}

/** Tarif khusus hanya ditandai untuk plat merah (kendaraan dinas) */
export function keteranganTarif(h: TaxCalculationResult): string | null {
  return h.warnaPlat === "merah"
    ? `Tarif PKB ${persen(h.tarifPkbBerjalan)} · Plat ${WARNA_PLAT_LABEL[h.warnaPlat]}`
    : null
}

/** Teks ringkasan untuk disalin, seperti tombol salin di kalkulator-pajak */
export function ringkasanPenetapan(h: TaxCalculationResult, nopol?: string): string {
  const [pkb, opsen, swdkllj] = rincianPembayaran(h)
  return [
    "RINGKASAN PENETAPAN PAJAK KENDARAAN",
    nopol ? `Nomor polisi: ${nopol}` : null,
    h.warnaPlat === "merah" ? `Plat ${WARNA_PLAT_LABEL[h.warnaPlat]} · Tarif PKB ${persen(h.tarifPkbBerjalan)}` : null,
    `PKB: ${rupiah(pkb.jumlah)}`,
    `Opsen PKB: ${rupiah(opsen.jumlah)}`,
    `SWDKLLJ: ${rupiah(swdkllj.jumlah)}`,
    `PNBP: ${rupiah(h.biayaStnk + h.biayaTnkb)}`,
    `Total bayar: ${rupiah(h.total)}`,
    "Denda PKB dan Opsen: dibebaskan 100%",
  ]
    .filter(Boolean)
    .join("\n")
}
