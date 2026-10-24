/*
 * Laporan Penerimaan (D-047): penerimaan PKB, BBNKB, dan opsen per loket dari
 * berkas STS sistem BPAD.
 *
 * Data ini selalu dibaca dari server PINTU, apa pun sumber data konten
 * dashboard. Laporan dijumlahkan oleh server tanpa identitas wajib pajak.
 * Nomor polisi, nama pemilik, RT/RW, dan kolom STS lain hanya disimpan
 * (terenkripsi) untuk transaksi 7 hari terakhir dan hanya dibaca Rincian
 * penerimaan (D-079); portal tidak pernah menerimanya. Kontraknya ada di
 * docs/api.md.
 */
import { keepPreviousData, useMutation, useQuery, useQueryClient } from "@tanstack/react-query"

import { mintaServer } from "@/lib/api/http"
import { SAMA_ORIGIN } from "@/lib/auth"
import { hariIniIso } from "@/lib/format"

import { KOLOM_CSV, teksCsv, unduhTeks, type TeksAngka } from "../../portal/penerimaan/csv"

export type JenisLoket = "kantor" | "keliling" | "gerai" | "sementara"

export interface LoketPenerimaan {
  kunci: string
  nama: string
  jenis: JenisLoket
}

/** Jumlah transaksi dan rupiah setiap komponen, dalam urutan berkas STS */
export interface Jumlah {
  transaksi: number
  pkb: number
  bbn: number
  dendaPkb: number
  dendaBbn: number
  tunggakan: number
  dendaTunggakan: number
  opsenPkb: number
  opsenBbn: number
  opsenDendaPkb: number
  opsenDendaBbn: number
  opsenTunggakan: number
  opsenDendaTunggakan: number
  jumlah: number
}

export interface BarisRincian extends Jumlah {
  nilai: string
  /** Hanya pada rincian loket: nama loket menurut impor terakhirnya */
  nama?: string
  /** Hanya pada rincian kelurahan */
  kecamatan?: string
}

/** Saringan dan rincian laporan, dengan nama parameter server */
export const DIMENSI = [
  "loket",
  "jenisDaftar",
  "jenisKendaraan",
  "kepemilikan",
  "warnaPlat",
  "merk",
  "uptAsal",
  "kecamatan",
  "kelurahan",
] as const

export type Dimensi = (typeof DIMENSI)[number]

export const LABEL_DIMENSI: Record<Dimensi, string> = {
  loket: "Loket",
  jenisDaftar: "Jenis daftar",
  jenisKendaraan: "Jenis kendaraan",
  kepemilikan: "Kepemilikan",
  warnaPlat: "Warna plat",
  merk: "Merek",
  uptAsal: "Asal UPT",
  kecamatan: "Kecamatan",
  kelurahan: "Kelurahan",
}

export const LABEL_KEPEMILIKAN: Record<string, string> = {
  pribadi: "Pribadi",
  umum: "Umum",
  pemerintah: "Pemerintah",
  lainnya: "Lainnya",
}

export interface LaporanPenerimaan {
  dari: string
  sampai: string
  ringkasan: Jumlah
  /** Periode pembanding; null bila tidak ada hari sebelumnya yang berisi transaksi */
  pembanding: { dari: string; sampai: string; ringkasan: Jumlah } | null
  rincian: Record<Dimensi | "tanggal", BarisRincian[]>
  /** Nilai yang ada dalam periode, untuk daftar saringan */
  pilihan: { loket: { nilai: string; nama: string }[] } & Record<Exclude<Dimensi, "loket">, string[]>
  /** Loket yang melayani pada hari itu (hanya untuk laporan satu hari) */
  loketBuka: LoketPenerimaan[]
}

export interface Saringan {
  dari: string
  sampai: string
  pilih: Partial<Record<Dimensi, string[]>>
  tunggakan: "" | "ada" | "tidak"
}

export interface ImporPenerimaan {
  id: string
  loket: string
  namaLoket: string
  namaBerkas: string
  tanggalAwal: string
  tanggalAkhir: string
  jumlahTransaksi: number
  jumlahDilewati: number
  total: number
  tampil: boolean
  oleh: string | null
  dibuat: string
}

export interface RingkasanBerkas {
  tanggalAwal: string | null
  tanggalAkhir: string | null
  transaksi: number
  jumlah: number
  jenisDaftar: { nilai: string; transaksi: number; jumlah: number }[]
}

export interface PratinjauImpor {
  loket: LoketPenerimaan
  namaBerkas: string
  /** Seluruh transaksi di berkas */
  berkas: RingkasanBerkas
  /** Transaksi yang belum pernah diimpor; hanya ini yang disimpan */
  baru: RingkasanBerkas
  dilewati: number
  /**
   * Transaksi yang dilewati tetapi diimpor tanpa nomoratur (sebelum D-071) atau,
   * bila dari 7 hari terakhir, tanpa rincian (sebelum D-079); yang kosong diisi dari berkas ini
   */
  dilengkapi: number
}

/** Jawaban impor yang hanya melengkapi nomoratur atau rincian transaksi lama, tanpa impor baru (D-071, D-079) */
export interface HasilLengkapi {
  dilengkapi: number
}

/** Kolom berkas STS yang hanya disimpan untuk Rincian penerimaan, kosong (null) bila tidak tercatat (D-079) */
export interface RincianStsKolom {
  nopol: string | null
  nama: string | null
  tipeKendaraan: string | null
  jatuhTempoAwal: string | null
  jatuhTempoAkhir: string | null
  nilaiJual: string | null
  uptBayar: string | null
  kodeJenisKendaraan: string | null
  /** Kolom "JENIS KENDARAAN", mis. SEPEDA MOTOR PRIBADI */
  jenisKendaraan: string | null
  rt: string | null
  rw: string | null
  ubahBentuk: string | null
}

/** Satu transaksi STS di Rincian penerimaan (GET /api/penerimaan/rincian) */
export interface TransaksiRincian extends RincianStsKolom {
  id: string
  loket: string
  namaLoket: string
  tanggal: string
  jenisDaftar: string
  nomoratur: string | null
  /** Kolom JNS_KEND, mis. SEPEDA MOTOR */
  jnsKend: string
  merk: string
  warnaPlat: string
  uptAsal: string
  kelurahan: string
  kecamatan: string
  tahunTunggakan: number
  pkb: number
  bbn: number
  dendaPkb: number
  dendaBbn: number
  tunggakan: number
  dendaTunggakan: number
  opsenPkb: number
  opsenBbn: number
  opsenDendaPkb: number
  opsenDendaBbn: number
  opsenTunggakan: number
  opsenDendaTunggakan: number
  jumlah: number
}

export interface RincianPenerimaan {
  tanggal: string
  transaksi: TransaksiRincian[]
  /** Transaksi hari itu yang diimpor sebelum rinciannya dicatat */
  belumTercatat: number
}

/** Banyaknya hari, hari ini termasuk, yang rinciannya disimpan dan bisa dilihat (D-079), sama dengan server */
export const HARI_RINCIAN = 7

/** Hari pertama dan terakhir yang rinciannya tersedia: 6 hari lalu sampai hari ini (WITA) */
export function jendelaRincian(hariIni = hariIniIso()): { dari: string; sampai: string } {
  const d = new Date(`${hariIni}T00:00:00Z`)
  d.setUTCDate(d.getUTCDate() - (HARI_RINCIAN - 1))
  return { dari: d.toISOString().slice(0, 10), sampai: hariIni }
}

/** Ukuran berkas STS yang diterima server */
export const MAKS_BERKAS = 10 * 1024 * 1024

const KUNCI = ["penerimaan"] as const

function kueri(s: Saringan): string {
  const p = new URLSearchParams({ dari: s.dari, sampai: s.sampai })
  for (const d of DIMENSI) for (const nilai of s.pilih[d] ?? []) p.append(`${d}[]`, nilai)
  if (s.tunggakan) p.set("tunggakan", s.tunggakan)
  return p.toString()
}

export function useLaporanPenerimaan(saringan: Saringan | null) {
  return useQuery({
    queryKey: [...KUNCI, "laporan", saringan],
    queryFn: () => mintaServer<LaporanPenerimaan>(`/api/penerimaan/laporan?${kueri(saringan!)}`, "GET"),
    enabled: !!saringan,
    placeholderData: keepPreviousData,
  })
}

/**
 * Transaksi satu hari beserta rinciannya, dengan saringan laporan; hanya dimuat
 * untuk laporan Harian di dalam jendela 7 hari (saringan lain: null).
 */
export function useRincianPenerimaan(saringan: Saringan | null) {
  return useQuery({
    queryKey: [...KUNCI, "rincian", saringan],
    queryFn: () => {
      const p = new URLSearchParams(kueri(saringan!))
      p.delete("dari")
      p.delete("sampai")
      p.set("tanggal", saringan!.dari)
      return mintaServer<RincianPenerimaan>(`/api/penerimaan/rincian?${p.toString()}`, "GET")
    },
    enabled: !!saringan,
  })
}

export function useLoketPenerimaan() {
  return useQuery({
    queryKey: [...KUNCI, "loket"],
    queryFn: () => mintaServer<LoketPenerimaan[]>("/api/penerimaan/loket", "GET"),
  })
}

export function useDaftarImpor() {
  return useQuery({
    queryKey: [...KUNCI, "impor"],
    queryFn: () => mintaServer<ImporPenerimaan[]>("/api/penerimaan/impor", "GET"),
  })
}

function formImpor(berkas: File, loket: string): FormData {
  const form = new FormData()
  form.append("loket", loket)
  form.append("berkas", berkas, berkas.name)
  return form
}

/** Baca berkas di server tanpa menyimpannya */
export function usePratinjauImpor() {
  return useMutation({
    mutationFn: ({ berkas, loket }: { berkas: File; loket: string }) =>
      mintaServer<PratinjauImpor>("/api/penerimaan/impor/pratinjau", "POST", formImpor(berkas, loket)),
  })
}

export function useSimpanImpor() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ berkas, loket }: { berkas: File; loket: string }) =>
      mintaServer<ImporPenerimaan | HasilLengkapi>("/api/penerimaan/impor", "POST", formImpor(berkas, loket)),
    onSuccess: () => void qc.invalidateQueries({ queryKey: KUNCI }),
  })
}

/** Tampilkan impor di halaman Laporan Penerimaan portal atau sembunyikan; khusus Administrator (D-048) */
export function useTampilImpor() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, tampil }: { id: string; tampil: boolean }) =>
      mintaServer<ImporPenerimaan>(`/api/penerimaan/impor/${encodeURIComponent(id)}`, "PATCH", { tampil }),
    onSuccess: () => void qc.invalidateQueries({ queryKey: KUNCI }),
  })
}

export function useHapusImpor() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (ids: string[]) => mintaServer<void>("/api/penerimaan/impor/hapus", "POST", { ids }),
    onSuccess: () => void qc.invalidateQueries({ queryKey: KUNCI }),
  })
}

/** Pajak provinsi: PKB, BBNKB, denda, dan tunggakan beserta dendanya */
export function bagianProvinsi(j: Jumlah): number {
  return j.pkb + j.bbn + j.dendaPkb + j.dendaBbn + j.tunggakan + j.dendaTunggakan
}

/** Opsen kabupaten/kota asal kendaraan atas setiap komponen provinsi */
export function bagianOpsen(j: Jumlah): number {
  return j.opsenPkb + j.opsenBbn + j.opsenDendaPkb + j.opsenDendaBbn + j.opsenTunggakan + j.opsenDendaTunggakan
}

/** Komponen penerimaan untuk komposisi dan rincian; semua opsen dijumlah menjadi satu */
export const KOMPONEN: { kunci: string; label: string; nilai: (j: Jumlah) => number }[] = [
  { kunci: "pkb", label: "PKB", nilai: (j) => j.pkb },
  { kunci: "bbn", label: "BBNKB", nilai: (j) => j.bbn },
  { kunci: "denda", label: "Denda", nilai: (j) => j.dendaPkb + j.dendaBbn },
  { kunci: "tunggakan", label: "Tunggakan", nilai: (j) => j.tunggakan + j.dendaTunggakan },
  { kunci: "opsen", label: "Opsen", nilai: bagianOpsen },
]

/** Perubahan dalam persen; null bila pembandingnya nol */
export function perubahan(kini: number, lalu: number | undefined): number | null {
  if (lalu === undefined || lalu === 0) return null
  return ((kini - lalu) / lalu) * 100
}

/** Nama tampilan nilai suatu rincian */
export function labelNilai(dimensi: Dimensi | "tanggal", baris: Pick<BarisRincian, "nilai" | "nama">): string {
  // Kolom kosong di berkas STS disimpan sebagai "-"
  if (baris.nilai === "-") return "(tidak diisi)"
  if (dimensi === "loket") return baris.nama ?? baris.nilai
  if (dimensi === "kepemilikan") return LABEL_KEPEMILIKAN[baris.nilai] ?? baris.nilai
  return baris.nilai
}

/**
 * Rincian sebagai CSV berpemisah titik koma (pengaturan Excel berbahasa
 * Indonesia), dengan BOM agar Excel membacanya sebagai UTF-8.
 */
export function csvRincian(dimensi: Dimensi | "tanggal", baris: BarisRincian[], total: Jumlah): string {
  const judul = dimensi === "tanggal" ? "Tanggal" : LABEL_DIMENSI[dimensi]
  const kepala = [judul, ...(dimensi === "kelurahan" ? ["Kecamatan"] : []), ...KOLOM_CSV.map(([label]) => label)]
  const isi = baris.map((b) => [
    labelNilai(dimensi, b),
    ...(dimensi === "kelurahan" ? [b.kecamatan ?? ""] : []),
    ...KOLOM_CSV.map(([, kunci]) => b[kunci]),
  ])
  const jumlah = ["Jumlah", ...(dimensi === "kelurahan" ? [""] : []), ...KOLOM_CSV.map(([, kunci]) => total[kunci])]
  return teksCsv([kepala, ...isi, jumlah])
}

export { unduhTeks }

/* ---------- Realisasi target, Form I (D-068) ---------- */

/** Jenis penerimaan Form I, dengan nama isian API */
export type BagianRealisasi = "pkb" | "tunggakan" | "bbn" | "dendaPkb" | "dendaTunggakan" | "dendaBbn"

export const LABEL_BAGIAN: Record<BagianRealisasi, string> = {
  pkb: "PKB",
  tunggakan: "PKB Tunggakan",
  bbn: "BBNKB",
  dendaPkb: "Denda PKB",
  dendaTunggakan: "Denda PKB Tunggakan",
  dendaBbn: "Denda BBNKB",
}

/** Target tahunan; target PKB dan denda PKB masing-masing termasuk tunggakannya */
export type KunciTarget = "pkb" | "bbn" | "dendaPkb" | "dendaBbn"

/** Realisasi bulan-bulan sebelum data STS lengkap, diisi manual sampai bulan tertentu */
export type SaldoAwal = Record<BagianRealisasi, number> & { sampaiBulan: number }

export interface TargetPenerimaan extends Record<KunciTarget, number> {
  tahun: number
  saldoAwal: SaldoAwal | null
  diperbarui: string | null
  oleh: string | null
}

export interface BarisRealisasi {
  /** rincian, jumlah per target (JUMLAH PKB), jumlah per bagian (JUMLAH PAJAK), atau total */
  jenis: "rincian" | "subjumlah" | "jumlah" | "total"
  no: string | null
  uraian: string
  /** Banyak baris yang dicakup sel nomor dan target: 2 pada rincian pertama target bersama, 0 pada baris di bawahnya */
  baris: number
  /** null pada baris yang sel targetnya digabung dengan baris di atasnya */
  target: number | null
  lalu: number
  ini: number
  sampaiIni: number
  /** Persen realisasi s.d. bulan ini terhadap target; null bila targetnya bersama atau nol */
  persen: number | null
}

export interface RealisasiPenerimaan {
  tahun: number
  bulan: number
  /** Tanggal batas bulan ini ("YYYY-MM-DD", D-070); null bila sebulan penuh */
  sampai: string | null
  /** false bila saldo awal mencakup bulan ini, sehingga tidak ada laporan per bulannya */
  tersedia: boolean
  /** Tanggal transaksi terakhir bulan ini dari semua loket */
  sampaiTanggal: string | null
  loket: { nilai: string; nama: string; transaksi: number; jumlah: number; sampaiTanggal: string }[]
  baris: BarisRealisasi[]
  /** null bila target tahun ini belum diisi */
  target: TargetPenerimaan | null
}

export interface IsianTarget {
  target: Record<KunciTarget, number>
  saldoAwal: SaldoAwal | null
}

/** Bulan dan tanggal batas sebagai parameter alamat; tanpa tanggal batas berarti sebulan penuh */
function kueriRealisasi(bulan: string, sampai: string): string {
  const p = new URLSearchParams({ bulan })
  if (sampai) p.set("sampai", sampai)
  return p.toString()
}

/** Form I sebulan ("YYYY-MM") dari semua loket, sampai tanggal tertentu bila diisi (D-070) */
export function useRealisasi(bulan: string, sampai = "") {
  return useQuery({
    queryKey: [...KUNCI, "realisasi", bulan, sampai],
    queryFn: () => mintaServer<RealisasiPenerimaan>(`/api/penerimaan/realisasi?${kueriRealisasi(bulan, sampai)}`, "GET"),
    placeholderData: keepPreviousData,
  })
}

/** Simpan target dan saldo awal setahun */
export function useSimpanTarget() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ tahun, isian }: { tahun: number; isian: IsianTarget }) =>
      mintaServer<TargetPenerimaan>(`/api/penerimaan/target/${tahun}`, "PUT", isian),
    onSuccess: () => void qc.invalidateQueries({ queryKey: [...KUNCI, "realisasi"] }),
  })
}

/** Unduh Form I sebulan, atau sampai tanggal batasnya, sebagai berkas Excel buatan server */
export async function unduhRealisasi(bulan: string, sampai = ""): Promise<void> {
  let res: Response
  try {
    res = await fetch(`/api/penerimaan/realisasi/unduh?${kueriRealisasi(bulan, sampai)}`, {
      ...SAMA_ORIGIN,
      headers: { Accept: "application/json" },
    })
  } catch {
    throw new Error("Server tidak dapat dihubungi. Periksa koneksi internet Anda.")
  }
  if (!res.ok) {
    let pesan = `Berkas gagal dibuat (${res.status}).`
    try {
      const json = (await res.json()) as { message?: string; errors?: Record<string, string[]> }
      pesan = Object.values(json.errors ?? {})[0]?.[0] ?? json.message ?? pesan
    } catch {
      // respons bukan JSON
    }
    if (res.status === 401) pesan = "Sesi Anda berakhir. Silakan masuk kembali."
    throw new Error(pesan)
  }
  const url = URL.createObjectURL(await res.blob())
  const a = document.createElement("a")
  a.href = url
  a.download = `realisasi-penerimaan-${bulan}${sampai ? `-sd-${sampai.slice(8, 10)}` : ""}.xlsx`
  document.body.append(a)
  a.click()
  a.remove()
  setTimeout(() => URL.revokeObjectURL(url), 1000)
}

/* ---------- Nomoratur SKPD (D-071) ---------- */

/** Nomor SKPD yang berurutan, dari awal sampai akhir */
export interface RentangNomoratur {
  awal: string
  akhir: string
  lembar: number
}

/** Nomor yang tidak muncul di antara dua rentang yang jaraknya paling banyak batasTerlewat */
export interface TerlewatNomoratur {
  awal: string
  akhir: string
  jumlah: number
}

export interface JumlahNomoratur {
  /** Nomor SKPD berbeda yang terpakai */
  lembar: number
  /** Transaksi bernomoratur, termasuk nomor yang dipakai lebih dari sekali */
  transaksi: number
  /** Transaksi tanpa SKPD, mis. Mutasi keluar dan Tambahan pajak */
  tanpaNomoratur: number
  /** Transaksi dari impor sebelum nomoratur dicatat; lengkapi dengan mengimpor ulang berkasnya */
  belumTercatat: number
}

export interface HariNomoratur extends JumlahNomoratur {
  tanggal: string
  rentang: RentangNomoratur[]
  terlewat: TerlewatNomoratur[]
  /** Nomor hari itu yang dipakai lebih dari sekali dalam periode */
  ganda: string[]
}

export interface LoketNomoratur extends JumlahNomoratur {
  nilai: string
  nama: string
  /** Banyaknya nomor terlewat */
  terlewat: number
  /** Banyaknya nomor loket ini yang dipakai lebih dari sekali dalam periode */
  ganda: number
  hari: HariNomoratur[]
}

export interface LaporanNomoratur {
  dari: string
  sampai: string
  batasTerlewat: number
  ringkasan: JumlahNomoratur & { terlewat: number; ganda: number }
  loket: LoketNomoratur[]
  ganda: { nomor: string; kali: number; pemakaian: { loket: string; tanggal: string; jenisDaftar: string }[] }[]
}

/** Pemakaian nomoratur SKPD sehari atau sebulan, semua loket atau satu loket */
export function useNomoratur(dari: string | undefined, sampai: string | undefined, loket: string) {
  return useQuery({
    queryKey: [...KUNCI, "nomoratur", dari, sampai, loket],
    queryFn: () => {
      const p = new URLSearchParams({ dari: dari!, sampai: sampai! })
      if (loket) p.set("loket", loket)
      return mintaServer<LaporanNomoratur>(`/api/penerimaan/nomoratur?${p}`, "GET")
    },
    enabled: !!dari && !!sampai,
    placeholderData: keepPreviousData,
  })
}

/** "0000104–0000105" atau "0000104" bila hanya satu nomor */
export function labelRentang(r: { awal: string; akhir: string }): string {
  return r.awal === r.akhir ? r.awal : `${r.awal}–${r.akhir}`
}

/**
 * Nomoratur sebagai CSV: satu baris per rentang terpakai, nomor terlewat, dan
 * nomor ganda per loket per tanggal. Nomor ditulis sebagai teks agar Excel
 * tidak membuang nol di depannya.
 */
export function csvNomoratur(l: LaporanNomoratur): string {
  const nomor = (n: string): TeksAngka => ({ teksAngka: n })
  const baris: (string | number | TeksAngka)[][] = [["Loket", "Tanggal", "Jenis", "Nomor awal", "Nomor akhir", "Jumlah"]]
  for (const loket of l.loket) {
    for (const hari of loket.hari) {
      for (const r of hari.rentang) baris.push([loket.nama, hari.tanggal, "Terpakai", nomor(r.awal), nomor(r.akhir), r.lembar])
      for (const t of hari.terlewat) baris.push([loket.nama, hari.tanggal, "Terlewat", nomor(t.awal), nomor(t.akhir), t.jumlah])
      for (const g of hari.ganda) baris.push([loket.nama, hari.tanggal, "Ganda", nomor(g), nomor(g), 1])
    }
  }
  return teksCsv(baris)
}
