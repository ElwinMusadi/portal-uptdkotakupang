/*
 * Antrean Online (D-051): nomor antrean yang dicetak di Meja Informasi setelah
 * berkas lengkap, dan layanan yang dicetak nomornya. Selalu dibaca dari server
 * PINTU; kontraknya ada di docs/api.md.
 */
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query"

import { mintaServer } from "@/lib/api/http"

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

export interface TiketAntrean {
  id: string
  nomor: string
  /** Tanggal WITA, "YYYY-MM-DD" */
  tanggal: string
  layanan: { kode: string; nama: string }
  /** Seperti di plat, mis. "DH 1234 AB"; null bila dihapus Administrator */
  nopol: string | null
  status: "menunggu" | "batal"
  tahap: TahapAntrean | null
  tahapLayanan: TahapAntrean[]
  /** Alamat halaman lacak yang tersimpan di QR tiket */
  urlLacak: string
  dicetak: string | null
  dicetakOleh: string | null
  dibatalkan: string | null
  alasanBatal: string | null
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
