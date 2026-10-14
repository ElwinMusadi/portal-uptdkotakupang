/*
 * Kegiatan lapangan dan clock-in petugas, khusus Administrator (D-034).
 *
 * Data ini selalu dibaca dari server PINTU, apa pun sumber data konten
 * dashboard; tidak ada salinannya di IndexedDB. Petugas melakukan clock-in
 * lewat aplikasi Absensi PINTU di /absen. Kontraknya ada di docs/api.md.
 */
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query"

import { mintaServer } from "@/lib/api/http"

export interface PetugasKegiatan {
  id: string
  nama: string
  nip: string
}

export interface AbsenPetugas {
  id: string
  petugas: PetugasKegiatan
  waktu: string
  lat: number
  lng: number
  akurasi: number
  /** Jarak ke titik kegiatan saat clock-in, dalam meter */
  jarak: number
  /** Swafoto di bucket privat; hanya bisa dibuka petugas itu dan Administrator */
  foto: string
}

export interface KegiatanLapangan {
  id: string
  nama: string
  tanggal: string
  tempat: string
  lat: number
  lng: number
  /** Radius clock-in dari titik kegiatan, dalam meter */
  radius: number
  catatan: string
  petugas: PetugasKegiatan[]
  jumlahHadir: number
  /** Hanya pada rekap satu kegiatan */
  absen?: AbsenPetugas[]
  dibuat: string
  diperbarui: string
}

export interface DataKegiatan {
  nama: string
  tanggal: string
  tempat: string
  lat: number
  lng: number
  radius: number
  catatan: string
  petugas: string[]
}

/** Radius yang diterima server, dalam meter */
export const RADIUS_MIN = 20
export const RADIUS_MAKS = 2000

const KUNCI = ["absensi", "kegiatan"] as const
const url = (id?: string) => `/api/absensi/kegiatan${id ? `/${encodeURIComponent(id)}` : ""}`

export function useDaftarKegiatan(aktif = true) {
  return useQuery({
    queryKey: KUNCI,
    queryFn: () => mintaServer<KegiatanLapangan[]>(url(), "GET"),
    enabled: aktif,
    refetchOnWindowFocus: true,
  })
}

/** Rekap satu kegiatan beserta clock-in petugasnya */
export function useRekapKegiatan(id: string | null) {
  return useQuery({
    queryKey: [...KUNCI, id],
    queryFn: () => mintaServer<KegiatanLapangan>(url(id ?? ""), "GET"),
    enabled: !!id,
    refetchOnWindowFocus: true,
  })
}

export function useSimpanKegiatan() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, data }: { id?: string; data: DataKegiatan }) =>
      mintaServer<KegiatanLapangan>(url(id), id ? "PATCH" : "POST", data),
    onSuccess: () => void qc.invalidateQueries({ queryKey: KUNCI }),
  })
}

export function useHapusKegiatan() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (ids: string[]) => mintaServer<void>(`${url()}/hapus`, "POST", { ids }),
    onSuccess: () => void qc.invalidateQueries({ queryKey: KUNCI }),
  })
}

/** Tautan Google Maps untuk satu titik */
export function urlPeta(lat: number, lng: number) {
  return `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`
}
