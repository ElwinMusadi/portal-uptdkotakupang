/*
 * Pemetaan PKB (D-052): berkas Data Potensi dari sistem BPAD (.xls atau
 * .xlsx, D-064), dibaca di peramban oleh berkas-potensi.ts dan dikirim ke
 * server PINTU per bagian, lalu dipetakan per kecamatan dan kelurahan Kota
 * Kupang. Dashboard menyimpan semua kolom termasuk identitas wajib pajak
 * untuk sasaran penagihan (keputusan pemilik). Kontraknya ada di docs/api.md.
 */
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query"

import { mintaServer } from "@/lib/api/http"

import { bacaBerkasPotensi, KOLOM_POTENSI } from "./berkas-potensi"

export { KOLOM_POTENSI, periksaBerkasPotensi, type PemeriksaanBerkas } from "./berkas-potensi"

export type Kelompok = "taat" | "jatuh-tempo" | "terlambat" | "menunggak" | "lainnya"
export type Roda = "r2" | "r3" | "r4"

export const LABEL_KELOMPOK: Record<Kelompok, string> = {
  taat: "Taat",
  "jatuh-tempo": "Jatuh tempo hari ini",
  terlambat: "Terlambat < 1 tahun",
  menunggak: "Menunggak ≥ 1 tahun",
  lainnya: "Lainnya",
}

export const LABEL_RODA: Record<Roda | "lain", string> = { r2: "Roda dua", r3: "Roda tiga", r4: "Roda empat atau lebih", lain: "Jenis kosong" }

export const NAMA_BULAN = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"]

/** Kecamatan Kota Kupang dalam urutan resmi; "tanpa" untuk kendaraan tanpa kelurahan yang dikenal */
export const KECAMATAN = [
  { kunci: "ALAK", nama: "Alak" },
  { kunci: "KELAPA LIMA", nama: "Kelapa Lima" },
  { kunci: "KOTA LAMA", nama: "Kota Lama" },
  { kunci: "KOTA RAJA", nama: "Kota Raja" },
  { kunci: "MAULAFA", nama: "Maulafa" },
  { kunci: "OEBOBO", nama: "Oebobo" },
] as const

export interface ImporPotensi {
  id: string
  berkas: string
  jumlah: number
  diterima: number
  /** Tanggal penetapan di berkas, "YYYY-MM-DD" */
  tanggalPenetapan: string | null
  diimporOleh: string | null
  dimulai: string | null
  selesai: string | null
  /** Angka gabungannya tampil di portal (dinyalakan Administrator) */
  tampil: boolean
}

/** Angka satu wilayah: jumlah kendaraan per roda dan kepatuhan, dan rupiahnya */
export interface AngkaPotensi {
  kendaraan: number
  roda: Record<Roda | "lain", number>
  kelompok: Record<Kelompok, number>
  pokokPajak: number
  dendaBerjalan: number
  pokokTunggak: number
  dendaTunggak: number
  /** Pokok dan denda berjalan ditambah tunggakan, untuk kendaraan yang belum taat */
  tagihan: number
}

export interface KecamatanPotensi extends AngkaPotensi {
  /** Nama kecamatan seperti di berkas; kosong untuk kendaraan tanpa kelurahan yang dikenal */
  kunci: string
  nama: string
  kelurahan: (AngkaPotensi & { nama: string })[]
}

export interface RingkasanPemetaan {
  impor: ImporPotensi | null
  bulanDipilih?: number | null
  jumlah?: AngkaPotensi
  kecamatan?: KecamatanPotensi[]
  tunggakan?: { tahun: number; kendaraan: number; pokokTunggak: number; dendaTunggak: number }[]
  jenis?: { jenis: string; roda: Roda | null; kendaraan: number }[]
  kunjungan?: { hasil: string; kendaraan: number }[]
  bulan?: { bulan: number; kendaraan: number }[]
}

export interface KendaraanPotensi {
  id: number
  nopol: string
  nama: string
  alamat: string
  rt: string
  rw: string
  kelurahan: string
  kecamatan: string
  telepon: string | null
  jenis: string
  roda: Roda | null
  merk: string
  tahunBuat: number | null
  kepatuhan: string
  kelompok: Kelompok
  tahunTunggak: number
  sdNotice: string | null
  pokokPajak: number
  dendaBerjalan: number
  pokokTunggak: number
  dendaTunggak: number
  tagihan: number
  hasilKunjungan: string
}

export interface KendaraanLengkap extends KendaraanPotensi {
  ktp: string | null
  kecamatanBerkas: string
  tipe: string
  mesin: string
  rangka: string
  warnaPlat: string
  warnaKendaraan: string
  bbm: string
  tanggalPenetapan: string | null
  uptAsal: string
  penggunaan: string
  cyl: number | null
  kodeJr: string
  sdStnk: string | null
  rubahBentuk: string
  njkb: number
  njub: number
  bobot: number | null
  bedaHari: number
  bedaBulan: number
  bulatBulan: number
  tahunTunggakAwal: number
  peringatan: string
  kegiatanKunjungan: string
  petugasKunjungan: string
}

export interface SaringanSasaran {
  q: string
  kecamatan: string
  kelurahan: string
  kelompok: Kelompok | typeof BELUM_TAAT | ""
  roda: Roda | ""
  tahun: string
  bulan: string
}

/** Saringan kepatuhan untuk semua kendaraan yang belum taat (semua selain Taat) */
export const BELUM_TAAT = "belum-taat"

/** Daftar sasaran mulai dari kendaraan yang belum taat; "Semua status" tetap bisa dipilih */
export const SARINGAN_SASARAN: SaringanSasaran = { q: "", kecamatan: "", kelurahan: "", kelompok: BELUM_TAAT, roda: "", tahun: "", bulan: "" }

/** Kendaraan terbanyak dalam satu pencarian sasaran, sama dengan batas server */
export const BATAS_SASARAN = 200

const KUNCI = ["pemetaan"] as const

export function useImporPotensi() {
  return useQuery({
    queryKey: [...KUNCI, "impor"],
    queryFn: () => mintaServer<{ impor: ImporPotensi | null }>("/api/pemetaan/impor", "GET"),
  })
}

export function useRingkasanPemetaan(bulan: number | null) {
  return useQuery({
    queryKey: [...KUNCI, "ringkasan", bulan],
    queryFn: () => mintaServer<RingkasanPemetaan>(`/api/pemetaan/ringkasan${bulan ? `?bulan=${bulan}` : ""}`, "GET"),
  })
}

/** Kendaraan yang cocok, tagihan terbesar dulu; isian kosong tidak dikirim */
export function useSasaranPenagihan(saringan: SaringanSasaran) {
  const isi = new URLSearchParams(Object.entries(saringan).filter(([, nilai]) => nilai.trim() !== ""))
  return useQuery({
    queryKey: [...KUNCI, "kendaraan", isi.toString()],
    queryFn: () => mintaServer<{ jumlah: number; kendaraan: KendaraanPotensi[] }>(`/api/pemetaan/kendaraan?${isi.toString()}`, "GET"),
  })
}

export function useDetailKendaraan(id: number | null) {
  return useQuery({
    queryKey: [...KUNCI, "detail", id],
    queryFn: () => mintaServer<KendaraanLengkap>(`/api/pemetaan/kendaraan/${id}`, "GET"),
    enabled: id !== null,
  })
}

/** Tampilkan angka gabungan di portal atau sembunyikan; khusus Administrator */
export function useTampilPotensi() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (tampil: boolean) => mintaServer<ImporPotensi>("/api/pemetaan/impor", "PATCH", { tampil }),
    onSuccess: () => void qc.invalidateQueries({ queryKey: KUNCI }),
  })
}

/** Kolom tabel kecamatan dan kelurahan (dashboard dan portal), berurutan */
export const KOLOM_WILAYAH = {
  kendaraan: "Kendaraan",
  rodaDua: "Roda dua",
  rodaTiga: "Roda tiga",
  rodaEmpat: "Roda empat+",
  taat: "Taat",
  belumBayar: "Belum bayar < 1 th",
  menunggak: "Menunggak ≥ 1 th",
  persenMenunggak: "% menunggak",
  potensi: "Potensi pokok",
  tagihan: "Tagihan",
  tunggakan: "Tunggakan",
} as const
export type KolomWilayah = keyof typeof KOLOM_WILAYAH

/** Kolom daftar sasaran penagihan (hanya dashboard), berurutan; nomor polisi selalu tampil */
export const KOLOM_SASARAN = {
  pemilik: "Pemilik",
  telepon: "Telepon",
  kendaraan: "Kendaraan",
  kecamatan: "Kecamatan",
  kepatuhan: "Kepatuhan",
  lamaTunggakan: "Lama tunggakan",
  pokokPajak: "Pokok pajak",
  tunggakan: "Tunggakan",
  tagihan: "Tagihan",
  kunjungan: "Hasil kunjungan",
} as const
export type KolomSasaran = keyof typeof KOLOM_SASARAN

export interface KolomPemetaan {
  wilayah: KolomWilayah[]
  sasaran: KolomSasaran[]
}

/** Kolom sebelum Administrator memilihnya; sama dengan Setting::potentialMapColumns() */
export const KOLOM_BAWAAN: KolomPemetaan = {
  wilayah: ["kendaraan", "rodaDua", "rodaTiga", "rodaEmpat", "taat", "belumBayar", "menunggak", "persenMenunggak", "tagihan"],
  sasaran: ["pemilik", "telepon", "kendaraan", "kepatuhan", "tagihan"],
}

/** Kolom yang tampil di tabel pemetaan; dipilih Administrator dan tersimpan di server */
export function useKolomPemetaan() {
  return useQuery({
    queryKey: [...KUNCI, "kolom"],
    queryFn: () => mintaServer<KolomPemetaan>("/api/pemetaan/kolom", "GET"),
    staleTime: 60_000,
  })
}

/** Simpan pilihan kolom; khusus Administrator, juga berlaku di portal untuk tabel wilayah */
export function useSimpanKolomPemetaan() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (kolom: KolomPemetaan) => mintaServer<KolomPemetaan>("/api/pemetaan/kolom", "PUT", kolom),
    onSuccess: (kolom) => qc.setQueryData([...KUNCI, "kolom"], kolom),
  })
}

/** Hapus seluruh data potensi yang sedang ditampilkan; khusus Administrator */
export function useHapusPotensi() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: () => mintaServer<void>("/api/pemetaan/impor", "DELETE"),
    onSuccess: () => void qc.invalidateQueries({ queryKey: KUNCI }),
  })
}

/**
 * Lintasan kedua: mengumumkan impor, mengirim baris per bagian sesuai
 * urutan berkas, lalu menyelesaikannya. Impor yang selesai menggantikan data
 * sebelumnya; impor yang terhenti di tengah dibuang saat impor berikutnya
 * dimulai.
 */
export async function imporBerkasPotensi(
  berkas: File,
  jumlah: number,
  kemajuan: (diterima: number) => void,
  sinyal?: AbortSignal
): Promise<ImporPotensi> {
  const mulai = await mintaServer<{ impor: ImporPotensi; barisPerBagian: number }>("/api/pemetaan/impor", "POST", {
    berkas: berkas.name,
    kolom: KOLOM_POTENSI,
    jumlah,
  })
  const id = encodeURIComponent(mulai.impor.id)
  let bagian: string[][] = []
  let terkirim = 0
  const kirim = async () => {
    if (bagian.length === 0) return
    if (sinyal?.aborted) throw new DOMException("Dibatalkan", "AbortError")
    const hasil = await mintaServer<{ diterima: number }>(`/api/pemetaan/impor/${id}/baris`, "POST", { mulai: terkirim, baris: bagian })
    terkirim = hasil.diterima
    bagian = []
    kemajuan(terkirim)
  }
  await bacaBerkasPotensi(
    berkas,
    async (sel) => {
      bagian.push(sel)
      if (bagian.length >= mulai.barisPerBagian) await kirim()
    },
    sinyal
  )
  await kirim()
  return mintaServer<ImporPotensi>(`/api/pemetaan/impor/${id}/selesai`, "POST")
}

/** "NAIKOTEN I" → "Naikoten I", "LAI-LAI BESI KOPAN" → "Lai-Lai Besi Kopan" */
export function namaWilayah(teks: string): string {
  if (!teks) return "Tanpa kelurahan"
  return teks
    .toLowerCase()
    .replace(/(^|[\s-])([a-z])/g, (_, a: string, b: string) => a + b.toUpperCase())
    .replace(/\b(Ii|Iii|Iv)\b/g, (r) => r.toUpperCase())
}
