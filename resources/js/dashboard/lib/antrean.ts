/*
 * Antrean Online (D-051): nomor antrean yang dicetak di Meja Informasi setelah
 * berkas lengkap, dan layanan yang dicetak nomornya. Selalu dibaca dari server
 * PINTU; kontraknya ada di docs/api.md.
 */
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query"

import { mintaServer } from "@/lib/api/http"
import { hariIniIso, tanggal, tanggalJam, ZONA_WAKTU } from "@/lib/format"

import { teksCsv, unduhTeks } from "../../portal/penerimaan/csv"

export type TahapAntrean = "pendaftaran" | "penetapan" | "kwitansi" | "pembayaran" | "cetak-skpd" | "cetak-stnk" | "penyerahan"

/** Tahap dalam urutan alur flowchart pemilik */
export const TAHAP: { kunci: TahapAntrean; label: string }[] = [
  { kunci: "pendaftaran", label: "Loket 1 – Pendaftaran" },
  { kunci: "penetapan", label: "Penetapan" },
  { kunci: "kwitansi", label: "Loket Kwitansi" },
  { kunci: "pembayaran", label: "Pembayaran" },
  { kunci: "cetak-skpd", label: "Cetak SKPD" },
  { kunci: "cetak-stnk", label: "Cetak STNK" },
  { kunci: "penyerahan", label: "Loket 2 – Penyerahan" },
]

export const LABEL_TAHAP = Object.fromEntries(TAHAP.map((t) => [t.kunci, t.label])) as Record<TahapAntrean, string>

export interface LayananAntrean {
  id: string
  kode: string
  nama: string
  cakupan: string
  tahap: TahapAntrean[]
  aktif: boolean
}

/** Tahap yang nomornya dipanggil ke loket; tahap lain ditandai petugas */
export const TAHAP_PANGGIL: TahapAntrean[] = ["pendaftaran", "kwitansi", "penyerahan"]

export type StatusTiket = "menunggu" | "dipanggil" | "ditunda" | "perubahan-data" | "selesai" | "batal"

export const LABEL_STATUS: Record<StatusTiket, string> = {
  menunggu: "Menunggu",
  dipanggil: "Dipanggil",
  ditunda: "Ditunda",
  "perubahan-data": "Menunggu perubahan data",
  selesai: "Selesai",
  batal: "Dibatalkan",
}

export type PosAntrean = "penetapan" | "server" | "pelunasan" | "cetak-skpd" | "cetak-stnk"

/** Pos yang menandai tahap selesai (Irisan 3); Server mengubah data kendaraan atas permintaan Penetapan */
export const POS: { kunci: PosAntrean; label: string; tahap: TahapAntrean }[] = [
  { kunci: "penetapan", label: "Penetapan", tahap: "penetapan" },
  { kunci: "server", label: "Server – Perubahan data", tahap: "penetapan" },
  { kunci: "pelunasan", label: "Pelunasan", tahap: "pembayaran" },
  { kunci: "cetak-skpd", label: "Cetak SKPD", tahap: "cetak-skpd" },
  { kunci: "cetak-stnk", label: "Cetak STNK", tahap: "cetak-stnk" },
]

export interface TiketAntrean {
  id: string
  nomor: string
  /** Tanggal WITA, "YYYY-MM-DD" */
  tanggal: string
  layanan: { kode: string; nama: string }
  /** Seperti di plat, mis. "DH 1234 AB"; null bila dihapus Administrator */
  nopol: string | null
  status: StatusTiket
  tahap: TahapAntrean | null
  tahapLayanan: TahapAntrean[]
  /** Alamat halaman lacak yang tersimpan di QR tiket */
  urlLacak: string
  /** Loket yang sedang memanggilnya */
  loket: string | null
  dipanggil: string | null
  jumlahPanggil: number
  masukTahap: string | null
  dicetak: string | null
  dicetakOleh: string | null
  dibatalkan: string | null
  alasanBatal: string | null
  selesai: string | null
}

export interface LoketAntrean {
  id: string
  nama: string
  tahap: TahapAntrean
  /** Kode layanan yang diambil "Panggil berikutnya"; kosong = semua */
  layanan: string[]
  namaSuara: string
  aktif: boolean
}

export interface MejaLoket {
  loket: LoketAntrean
  dipanggil: TiketAntrean | null
  menunggu: TiketAntrean[]
  ditunda: TiketAntrean[]
}

export interface LayarAntrean {
  id: string
  nama: string
  terakhirTerlihat: string | null
  dibuat: string | null
  dibuatOleh: string | null
}

/** "A-001" atau "A-001 · 14 Okt" untuk nomor hari sebelumnya */
export function nomorBertanggal(t: Pick<TiketAntrean, "nomor" | "tanggal">): string {
  return t.tanggal === hariIniIso() ? t.nomor : `${t.nomor} · ${tanggal(t.tanggal)}`
}

/** Jam saja dari waktu ISO, mis. "10.32" */
export const jamSaja = (iso: string | null) => (iso ? tanggalJam(iso).split(", ").pop() : "—")

/** Tahap sesudah tahap ini pada tiket, atau null bila ini yang terakhir */
export function tahapBerikutnya(t: Pick<TiketAntrean, "tahap" | "tahapLayanan">): TahapAntrean | null {
  const urutan = TAHAP.map((x) => x.kunci).filter((k) => t.tahapLayanan.includes(k))
  const i = t.tahap ? urutan.indexOf(t.tahap) : -1
  return i >= 0 ? (urutan[i + 1] ?? null) : null
}

const KUNCI = ["antrean"] as const

export function useLayananAntrean() {
  return useQuery({
    queryKey: [...KUNCI, "layanan"],
    queryFn: () => mintaServer<LayananAntrean[]>("/api/antrean/layanan", "GET"),
  })
}

/** Ubah nama, cakupan, tahap, atau status aktif layanan; khusus Administrator */
export function useSimpanLayananAntrean() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, ...isi }: Pick<LayananAntrean, "id" | "nama" | "cakupan" | "tahap" | "aktif">) =>
      mintaServer<LayananAntrean>(`/api/antrean/layanan/${encodeURIComponent(id)}`, "PUT", isi),
    onSuccess: () => void qc.invalidateQueries({ queryKey: [...KUNCI, "layanan"] }),
  })
}

/** Nomor yang dicetak pada satu tanggal; diperbarui berkala agar cetakan meja lain ikut tampil */
export function useTiketAntrean(tanggal: string) {
  return useQuery({
    queryKey: [...KUNCI, "tiket", tanggal],
    queryFn: () => mintaServer<TiketAntrean[]>(`/api/antrean/tiket?tanggal=${encodeURIComponent(tanggal)}`, "GET"),
    refetchInterval: 15_000,
  })
}

export function useCetakTiket() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (isi: { nopol: string; layanan: string }) => mintaServer<TiketAntrean>("/api/antrean/tiket", "POST", isi),
    onSuccess: () => void qc.invalidateQueries({ queryKey: [...KUNCI, "tiket"] }),
  })
}

export function useCetakUlangTiket() {
  return useMutation({
    mutationFn: (id: string) => mintaServer<TiketAntrean>(`/api/antrean/tiket/${encodeURIComponent(id)}/cetak-ulang`, "POST"),
  })
}

export function useBatalkanTiket() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, alasan }: { id: string; alasan: string }) =>
      mintaServer<TiketAntrean>(`/api/antrean/tiket/${encodeURIComponent(id)}/batal`, "POST", { alasan }),
    onSuccess: () => void qc.invalidateQueries({ queryKey: [...KUNCI, "tiket"] }),
  })
}

/* ---------- Loket dan panggilan (Irisan 2) ---------- */

export function useLoketAntrean() {
  return useQuery({
    queryKey: [...KUNCI, "loket"],
    queryFn: () => mintaServer<LoketAntrean[]>("/api/antrean/loket", "GET"),
  })
}

/** Tambah (tanpa id) atau ubah loket; khusus Administrator */
export function useSimpanLoket() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, ...isi }: Omit<LoketAntrean, "id"> & { id?: string }) =>
      id ? mintaServer<LoketAntrean>(`/api/antrean/loket/${encodeURIComponent(id)}`, "PUT", isi) : mintaServer<LoketAntrean>("/api/antrean/loket", "POST", isi),
    onSuccess: () => void qc.invalidateQueries({ queryKey: [...KUNCI, "loket"] }),
  })
}

export function useHapusLoket() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: string) => mintaServer<void>(`/api/antrean/loket/${encodeURIComponent(id)}`, "DELETE"),
    onSuccess: () => void qc.invalidateQueries({ queryKey: [...KUNCI, "loket"] }),
  })
}

/** Meja satu loket: nomor yang dipanggil, menunggu, dan ditunda; diperbarui setiap 3 detik */
export function useMejaLoket(id: string | null) {
  return useQuery({
    queryKey: [...KUNCI, "meja", id],
    queryFn: () => mintaServer<MejaLoket>(`/api/antrean/loket/${encodeURIComponent(id!)}/antrean`, "GET"),
    enabled: !!id,
    refetchInterval: 3_000,
  })
}

/** Panggil nomor berikutnya, atau nomor tertentu, ke loket */
export function usePanggil() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ loket, tiket }: { loket: string; tiket?: string }) =>
      mintaServer<TiketAntrean>(`/api/antrean/loket/${encodeURIComponent(loket)}/panggil`, "POST", tiket ? { tiket } : {}),
    onSuccess: () => void qc.invalidateQueries({ queryKey: KUNCI }),
  })
}

/** Panggil ulang, tunda, atau selesaikan tahap nomor yang sedang dipanggil */
export function useTindakanPanggilan() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, tindakan }: { id: string; tindakan: "panggil-ulang" | "tunda" | "selesai" }) =>
      mintaServer<TiketAntrean>(`/api/antrean/tiket/${encodeURIComponent(id)}/${tindakan}`, "POST"),
    onSuccess: () => void qc.invalidateQueries({ queryKey: KUNCI }),
  })
}

/** Nomor yang masih berjalan menurut QR, nomor antrean, atau nomor polisi */
export function cariTiket(q: string): Promise<TiketAntrean[]> {
  return mintaServer<TiketAntrean[]>(`/api/antrean/cari?q=${encodeURIComponent(q)}`, "GET")
}

/* ---------- Pos proses (Irisan 3) ---------- */

/** Nomor di tahap pos itu, paling lama menunggu lebih dulu; diperbarui setiap 3 detik */
export function useDaftarPos(pos: PosAntrean | null) {
  return useQuery({
    queryKey: [...KUNCI, "pos", pos],
    queryFn: () => mintaServer<TiketAntrean[]>(`/api/antrean/pos/${encodeURIComponent(pos!)}`, "GET"),
    enabled: !!pos,
    refetchInterval: 3_000,
  })
}

export type TandaPos = "selesai" | "perubahan-data"

/**
 * Tandai nomor di pos: tahapnya selesai (di Server: perubahan data selesai),
 * atau, di Penetapan, kirim berkas ke Server untuk perubahan data
 */
export function useTandaiPos() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ pos, tiket, tanda }: { pos: PosAntrean; tiket: string; tanda: TandaPos }) =>
      tanda === "perubahan-data"
        ? mintaServer<TiketAntrean>(`/api/antrean/tiket/${encodeURIComponent(tiket)}/perubahan-data`, "POST")
        : mintaServer<TiketAntrean>(`/api/antrean/pos/${encodeURIComponent(pos)}/tandai`, "POST", { tiket }),
    onSuccess: () => void qc.invalidateQueries({ queryKey: KUNCI }),
  })
}

/** Urungkan tanda terakhir petugas pada nomor itu, selama nomornya belum berubah lagi */
export function useUrungkanTanda() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: string) => mintaServer<TiketAntrean>(`/api/antrean/tiket/${encodeURIComponent(id)}/urungkan`, "POST"),
    onSuccess: () => void qc.invalidateQueries({ queryKey: KUNCI }),
  })
}

/* ---------- Arsip dan ringkasan (Irisan 4) ---------- */

export interface RiwayatTiket {
  aksi: string
  tahap: TahapAntrean | null
  /** Alasan batal, atau aksi yang diurungkan */
  keterangan: string | null
  loket: string | null
  petugas: string | null
  otomatis: boolean
  waktu: string | null
}

export interface TiketBerriwayat extends TiketAntrean {
  riwayat: RiwayatTiket[]
}

export interface SaringanArsip {
  q: string
  dari: string
  sampai: string
  layanan: string
  status: StatusTiket | ""
  tahap: TahapAntrean | ""
}

export const SARINGAN_KOSONG: SaringanArsip = { q: "", dari: "", sampai: "", layanan: "", status: "", tahap: "" }

/** Nomor terbanyak dalam satu pencarian arsip, sama dengan batas server */
export const BATAS_ARSIP = 200

/** Nomor terbaru yang cocok dengan pencarian; isian kosong tidak dikirim */
export function useArsipAntrean(saringan: SaringanArsip) {
  const isi = new URLSearchParams(Object.entries(saringan).filter(([, nilai]) => nilai.trim() !== ""))
  return useQuery({
    queryKey: [...KUNCI, "arsip", isi.toString()],
    queryFn: () => mintaServer<TiketAntrean[]>(`/api/antrean/arsip?${isi.toString()}`, "GET"),
  })
}

export function useDetailTiket(id: string | null) {
  return useQuery({
    queryKey: [...KUNCI, "detail", id],
    queryFn: () => mintaServer<TiketBerriwayat>(`/api/antrean/tiket/${encodeURIComponent(id!)}`, "GET"),
    enabled: !!id,
  })
}

/** Hapus tiket beserta riwayatnya; khusus Administrator, hanya tiket selesai atau batal dari hari sebelumnya */
export function useHapusTiket() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: string) => mintaServer<void>(`/api/antrean/tiket/${encodeURIComponent(id)}`, "DELETE"),
    onSuccess: () => void qc.invalidateQueries({ queryKey: KUNCI }),
  })
}

/** Apakah tiket boleh dihapus: sudah selesai atau batal, dan dicetak sebelum hari ini (WITA) */
export function bolehDihapus(t: Pick<TiketAntrean, "status" | "tanggal">): boolean {
  return (t.status === "selesai" || t.status === "batal") && t.tanggal < hariIniIso()
}

export interface JumlahAntrean {
  dicetak: number
  selesai: number
  batal: number
  berjalan: number
}

export interface TahapRingkasan {
  kunci: TahapAntrean
  nama: string
  /** Tahap yang nomornya dipanggil ke loket */
  dipanggil: boolean
  /** Ditandai selesai oleh loket atau pos */
  selesai: number
  /** Dilewati tanpa ditandai, dianggap selesai saat tahap sesudahnya diproses */
  otomatis: number
  /** Nomor yang pernah ditunda petugas di tahap ini */
  ditunda: number
  /** Nilai tengah waktu tunggu sampai dipanggil, dalam detik */
  tunggu: number | null
  /** Nilai tengah lama di tahap sampai selesai, dalam detik */
  lama: number | null
}

export interface RingkasanAntrean {
  dari: string
  sampai: string
  jumlah: JumlahAntrean & { ditunda: number }
  layanan: (JumlahAntrean & { kode: string; nama: string })[]
  harian: (JumlahAntrean & { tanggal: string })[]
  tahap: TahapRingkasan[]
}

/** Rentang ringkasan terpanjang yang diterima server, dalam hari */
export const MAKS_HARI_RINGKASAN = 31

export function useRingkasanAntrean(dari: string, sampai: string, aktif: boolean) {
  return useQuery({
    queryKey: [...KUNCI, "ringkasan", dari, sampai],
    queryFn: () => mintaServer<RingkasanAntrean>(`/api/antrean/ringkasan?dari=${encodeURIComponent(dari)}&sampai=${encodeURIComponent(sampai)}`, "GET"),
    enabled: aktif,
  })
}

/** Semua nomor yang dicetak pada hari-hari itu, untuk berkas CSV */
export function tiketRingkasan(dari: string, sampai: string): Promise<TiketAntrean[]> {
  return mintaServer<TiketAntrean[]>(`/api/antrean/ringkasan/tiket?dari=${encodeURIComponent(dari)}&sampai=${encodeURIComponent(sampai)}`, "GET")
}

/** "—", "<1 mnt", "12 mnt", atau "1 j 5 mnt" */
export function durasi(detik: number | null): string {
  if (detik === null) return "—"
  const menit = Math.round(detik / 60)
  if (menit < 1) return "<1 mnt"
  const jam = Math.floor(menit / 60)
  return jam === 0 ? `${menit} mnt` : `${jam} j${menit % 60 ? ` ${menit % 60} mnt` : ""}`
}

/** Satu baris riwayat tiket sebagai kalimat */
export function labelRiwayat(r: RiwayatTiket): string {
  const tahap = r.tahap ? LABEL_TAHAP[r.tahap] : ""
  switch (r.aksi) {
    case "dicetak":
      return "Dicetak di Meja Informasi"
    case "dicetak ulang":
      return "Dicetak ulang"
    case "dibatalkan":
      return "Dibatalkan"
    case "dipanggil":
      return `Dipanggil ke ${r.loket ?? tahap}`
    case "dipanggil ulang":
      return `Dipanggil ulang ke ${r.loket ?? tahap}`
    case "ditunda":
      return r.otomatis ? `Ditunda otomatis di ${r.loket ?? tahap} (loket memanggil nomor lain)` : `Ditunda di ${r.loket ?? tahap} (wajib pajak belum datang)`
    case "tahap selesai":
      return r.otomatis ? `${tahap} dilewati, dianggap selesai` : `${tahap} selesai`
    case "selesai":
      return "Berkas selesai diproses"
    case "perlu perubahan data":
      return "Dikirim ke Server untuk perubahan data"
    case "perubahan data selesai":
      return "Perubahan data selesai, kembali ke Penetapan"
    case "diurungkan":
      return `Tanda di ${tahap} diurungkan`
    default:
      return r.aksi
  }
}

const fmtCsv = new Intl.DateTimeFormat("sv-SE", { dateStyle: "short", timeStyle: "short", timeZone: ZONA_WAKTU })

/** Waktu WITA "2026-10-15 10:32" untuk berkas CSV */
const waktuCsv = (iso: string | null) => (iso ? fmtCsv.format(new Date(iso)) : "")

const menitCsv = (detik: number | null) => (detik === null ? "" : Math.round(detik / 60))

/** Berkas CSV ringkasan: per layanan, per tahap, per hari, lalu daftar nomor dengan nomor polisinya */
export function csvRingkasan(r: RingkasanAntrean, tiket: TiketAntrean[], periode: string): string {
  const jumlah = (j: JumlahAntrean) => [j.dicetak, j.selesai, j.batal, j.berjalan]
  return teksCsv([
    ["Ringkasan Antrean Online", periode],
    [],
    ["Layanan", "Dicetak", "Selesai", "Dibatalkan", "Masih berjalan"],
    ...r.layanan.map((l) => [`${l.kode} – ${l.nama}`, ...jumlah(l)]),
    ["Jumlah", ...jumlah(r.jumlah)],
    ["Pernah ditunda", r.jumlah.ditunda],
    [],
    ["Tahap", "Ditandai selesai", "Dilewati otomatis", "Pernah ditunda", "Tunggu dipanggil (menit, nilai tengah)", "Lama di tahap (menit, nilai tengah)"],
    ...r.tahap.map((t) => [t.nama, t.selesai, t.otomatis, t.ditunda, menitCsv(t.tunggu), menitCsv(t.lama)]),
    [],
    ["Tanggal", "Dicetak", "Selesai", "Dibatalkan", "Masih berjalan"],
    ...r.harian.map((h) => [h.tanggal, ...jumlah(h)]),
    [],
    ["Tanggal", "Nomor", "Layanan", "Nomor polisi", "Status", "Tahap terakhir", "Dicetak", "Dicetak oleh", "Selesai", "Dibatalkan", "Alasan batal"],
    ...tiket.map((t) => [
      t.tanggal,
      t.nomor,
      t.layanan.nama,
      t.nopol ?? "",
      LABEL_STATUS[t.status],
      t.tahap ? LABEL_TAHAP[t.tahap] : "",
      waktuCsv(t.dicetak),
      t.dicetakOleh ?? "",
      waktuCsv(t.selesai),
      waktuCsv(t.dibatalkan),
      t.alasanBatal ?? "",
    ]),
  ])
}

export { unduhTeks }

export function useLayarAntrean(aktif: boolean) {
  return useQuery({
    queryKey: [...KUNCI, "layar"],
    queryFn: () => mintaServer<LayarAntrean[]>("/api/antrean/layar", "GET"),
    enabled: aktif,
  })
}

/** Pasang layar baru: tautan pemasangan hanya dikembalikan sekali */
export function useTambahLayar() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (nama: string) => mintaServer<{ layar: LayarAntrean; tautan: string }>("/api/antrean/layar", "POST", { nama }),
    onSuccess: () => void qc.invalidateQueries({ queryKey: [...KUNCI, "layar"] }),
  })
}

export function useCabutLayar() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: string) => mintaServer<void>(`/api/antrean/layar/${encodeURIComponent(id)}`, "DELETE"),
    onSuccess: () => void qc.invalidateQueries({ queryKey: [...KUNCI, "layar"] }),
  })
}

/** Nomor polisi tanpa spasi dan tanda baca, huruf besar: "dh 1234-ab" → "DH1234AB" */
export function nopolRingkas(teks: string): string {
  return teks.toUpperCase().replace(/[^A-Z0-9]/g, "")
}

/** Apakah nomor polisi berbentuk 1–2 huruf, 1–4 angka, dan sampai 3 huruf, seperti diperiksa server */
export function nopolSah(teks: string): boolean {
  return /^[A-Z]{1,2}\d{1,4}[A-Z]{0,3}$/.test(nopolRingkas(teks))
}

/** Nomor polisi seperti di plat: "DH1234AB" → "DH 1234 AB" */
export function nopolPlat(teks: string): string {
  return nopolRingkas(teks).replace(/^([A-Z]+)(\d+)([A-Z]*)$/, "$1 $2 $3").trim()
}
