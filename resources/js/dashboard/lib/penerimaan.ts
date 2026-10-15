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

/** Rentang laporan terpanjang yang diterima server, dalam hari */
export const MAKS_HARI = 366

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

const KOLOM_CSV: [string, keyof Jumlah][] = [
  ["Transaksi", "transaksi"],
  ["PKB", "pkb"],
  ["BBNKB", "bbn"],
  ["Denda PKB", "dendaPkb"],
  ["Denda BBNKB", "dendaBbn"],
  ["Tunggakan", "tunggakan"],
  ["Denda Tunggakan", "dendaTunggakan"],
  ["Opsen PKB", "opsenPkb"],
  ["Opsen BBNKB", "opsenBbn"],
  ["Opsen Denda PKB", "opsenDendaPkb"],
  ["Opsen Denda BBNKB", "opsenDendaBbn"],
  ["Opsen Tunggakan", "opsenTunggakan"],
  ["Opsen Denda Tunggakan", "opsenDendaTunggakan"],
  ["Jumlah", "jumlah"],
]

function selCsv(teks: string | number): string {
  const s = String(teks)
  // Teks yang diawali =, +, -, atau @ dibaca Excel sebagai rumus
  const aman = typeof teks === "string" && s.length > 1 && /^[=+\-@\t\r]/.test(s) ? `'${s}` : s
  return /[";\n\r]/.test(aman) ? `"${aman.replace(/"/g, '""')}"` : aman
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
  return "﻿" + [kepala, ...isi, jumlah].map((r) => r.map(selCsv).join(";")).join("\r\n") + "\r\n"
}

/** Unduh teks sebagai berkas dari peramban */
export function unduhTeks(teks: string, nama: string, jenis = "text/csv;charset=utf-8") {
  const url = URL.createObjectURL(new Blob([teks], { type: jenis }))
  const a = document.createElement("a")
  a.href = url
  a.download = nama
  document.body.append(a)
  a.click()
  a.remove()
  setTimeout(() => URL.revokeObjectURL(url), 1000)
}
