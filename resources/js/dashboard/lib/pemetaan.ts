/*
 * Pemetaan PKB (D-052): berkas Data Potensi dari sistem BPAD, dibaca di
 * peramban dan dikirim ke server PINTU per bagian, lalu dipetakan per
 * kecamatan dan kelurahan Kota Kupang. Dashboard menyimpan semua kolom
 * termasuk identitas wajib pajak untuk sasaran penagihan (keputusan pemilik).
 * Kontraknya ada di docs/api.md.
 */
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query"

import { mintaServer } from "@/lib/api/http"

/** Kolom berkas Data Potensi, berurutan, seperti judul tabelnya */
export const KOLOM_POTENSI = [
  "NOPOL", "NAMA", "KTP", "TELEPON", "ALAMAT", "RT", "RW", "KELURAHAN", "KECAMATAN", "JENIS", "MERK", "TIPE",
  "MESIN", "RANGKA", "TAHUN BUAT", "WARNA PLAT", "WARNA KENDARAAN", "BBM", "TGL PENETAPAN", "UPT ASAL", "PENGGUNAAN", "CYL", "KODE JR",
  "SD STNK", "SD NOTICE", "RUBAH BENTUK", "NJKB", "NJUB", "BOBOT", "BEDA HARI BERJALAN", "BEDA BULAN BERLAJAN", "BULAT BULAN BERLAJAN",
  "TAHUN TUNGGAK", "TAHUN TUNGGAK AWAL", "POKOK PAJAK", "DENDA BERJALAN", "POKOK TUNGGAK", "DENDA TUNGGAK", "KEPATUHAN", "PERINGATAN",
  "STS KENDARAAN DTD", "KET GIAT DTD", "PETUGAS DTD",
] as const

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
  kelompok: Kelompok | ""
  roda: Roda | ""
  tahun: string
  bulan: string
}

export const SARINGAN_SASARAN: SaringanSasaran = { q: "", kecamatan: "", kelurahan: "", kelompok: "", roda: "", tahun: "", bulan: "" }

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

/** Hapus seluruh data potensi yang sedang ditampilkan; khusus Administrator */
export function useHapusPotensi() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: () => mintaServer<void>("/api/pemetaan/impor", "DELETE"),
    onSuccess: () => void qc.invalidateQueries({ queryKey: KUNCI }),
  })
}

/* ---------- Membaca berkas di peramban ---------- */

const ENTITAS: Record<string, string> = { amp: "&", lt: "<", gt: ">", quot: '"', apos: "'", nbsp: " " }

/** Teks satu sel: tanpa tag, entitas HTML dibaca, spasi dirapikan */
function teksSel(html: string): string {
  return html
    .replace(/<[^>]*>/g, "")
    .replace(/&(#x[0-9a-f]+|#\d+|[a-z]+);/gi, (asli, k: string) =>
      k[0] === "#" ? String.fromCodePoint(k[1].toLowerCase() === "x" ? parseInt(k.slice(2), 16) : Number(k.slice(1))) : (ENTITAS[k.toLowerCase()] ?? asli)
    )
    .replace(/\s+/g, " ")
    .trim()
}

/**
 * Membaca berkas Data Potensi (tabel HTML berakhiran .xls) sedikit demi
 * sedikit agar berkas setahun pun tidak memenuhi memori: judul kolom, lalu
 * setiap baris kendaraan. Baris tanpa sel atau baris catatan kaki (satu sel)
 * dilewati.
 */
export async function bacaBerkasPotensi(berkas: File, tiapBaris: (sel: string[]) => void | Promise<void>, sinyal?: AbortSignal): Promise<string[]> {
  const pembaca = berkas.stream().pipeThrough(new TextDecoderStream()).getReader()
  let sisa = ""
  let kolom: string[] | null = null
  try {
    for (;;) {
      if (sinyal?.aborted) throw new DOMException("Dibatalkan", "AbortError")
      const { value, done } = await pembaca.read()
      if (value) sisa += value
      if (!kolom) {
        const awalBaris = sisa.search(/<td[\s>]/i)
        if (awalBaris < 0 && !done) continue
        kolom = [...sisa.slice(0, awalBaris < 0 ? sisa.length : awalBaris).matchAll(/<th\b[^>]*>([\s\S]*?)<\/th>/gi)].map((m) => teksSel(m[1]))
      }
      const akhir = done ? sisa.length : sisa.toLowerCase().lastIndexOf("</tr>")
      if (akhir >= 0) {
        const potong = sisa.slice(0, done ? akhir : akhir + 5)
        sisa = done ? "" : sisa.slice(akhir + 5)
        for (const m of potong.matchAll(/<tr\b[^>]*>([\s\S]*?)<\/tr>/gi)) {
          const sel = [...m[1].matchAll(/<td\b[^>]*>([\s\S]*?)<\/td>/gi)].map((c) => teksSel(c[1]))
          if (sel.length > 1) await tiapBaris(sel)
        }
      }
      if (done) break
    }
  } finally {
    pembaca.releaseLock()
  }
  return kolom ?? []
}

export interface PemeriksaanBerkas {
  kolomCocok: boolean
  jumlah: number
  /** Tanggal penetapan yang muncul di berkas */
  tanggalPenetapan: string[]
  /** Baris yang jumlah selnya tidak sama dengan judul */
  barisRusak: number
}

/** Lintasan pertama: susunan kolom, banyaknya baris, dan tanggal penetapannya, tanpa mengirim apa pun */
export async function periksaBerkasPotensi(berkas: File, sinyal?: AbortSignal): Promise<PemeriksaanBerkas> {
  let jumlah = 0
  let barisRusak = 0
  const tanggal = new Set<string>()
  const kolom = await bacaBerkasPotensi(
    berkas,
    (sel) => {
      jumlah++
      if (sel.length !== KOLOM_POTENSI.length) barisRusak++
      else if (sel[18]) tanggal.add(sel[18])
    },
    sinyal
  )
  return {
    kolomCocok: kolom.length === KOLOM_POTENSI.length && kolom.every((k, i) => k === KOLOM_POTENSI[i]),
    jumlah,
    tanggalPenetapan: [...tanggal].sort(),
    barisRusak,
  }
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
