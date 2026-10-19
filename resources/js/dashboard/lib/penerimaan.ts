/*
 * Laporan Penerimaan (D-047): penerimaan PKB, BBNKB, dan opsen per loket dari
 * berkas STS sistem BPAD.
 *
 * Data ini selalu dibaca dari server PINTU, apa pun sumber data konten
 * dashboard. Server tidak menyimpan nama pemilik, nomor polisi, atau alamat di
 * bawah kelurahan; laporan dijumlahkan oleh server. Kontraknya ada di
 * docs/api.md.
 */
import { keepPreviousData, useMutation, useQuery, useQueryClient } from "@tanstack/react-query"

import { mintaServer } from "@/lib/api/http"
import { SAMA_ORIGIN } from "@/lib/auth"

import { KOLOM_CSV, teksCsv, unduhTeks } from "../../portal/penerimaan/csv"

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
      mintaServer<ImporPenerimaan>("/api/penerimaan/impor", "POST", formImpor(berkas, loket)),
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

/** Form I sebulan ("YYYY-MM") dari semua loket */
export function useRealisasi(bulan: string) {
  return useQuery({
    queryKey: [...KUNCI, "realisasi", bulan],
    queryFn: () => mintaServer<RealisasiPenerimaan>(`/api/penerimaan/realisasi?bulan=${encodeURIComponent(bulan)}`, "GET"),
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

/** Unduh Form I sebulan sebagai berkas Excel buatan server */
export async function unduhRealisasi(bulan: string): Promise<void> {
  let res: Response
  try {
    res = await fetch(`/api/penerimaan/realisasi/unduh?bulan=${encodeURIComponent(bulan)}`, {
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
  a.download = `realisasi-penerimaan-${bulan}.xlsx`
  document.body.append(a)
  a.click()
  a.remove()
  setTimeout(() => URL.revokeObjectURL(url), 1000)
}
