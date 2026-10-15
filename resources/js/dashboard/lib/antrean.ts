/*
 * Antrean Online (D-051): nomor antrean yang dicetak di Meja Informasi setelah
 * berkas lengkap, dan layanan yang dicetak nomornya. Selalu dibaca dari server
 * PINTU; kontraknya ada di docs/api.md.
 */
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query"

import { mintaServer } from "@/lib/api/http"
import { hariIniIso, tanggal, tanggalJam } from "@/lib/format"

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
