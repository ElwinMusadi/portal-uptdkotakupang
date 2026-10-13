/*
 * Akun pegawai, khusus Administrator.
 *
 * Data akun selalu dibaca dari server PINTU, apa pun sumber data konten
 * dashboard: tidak ada salinannya di IndexedDB. Kontraknya ada di docs/api.md.
 */
import {
  IconBan,
  IconCircleCheckFilled,
  IconHourglassHigh,
  IconPlayerPauseFilled,
  type Icon,
} from "@tabler/icons-react"
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query"

import { mintaServer } from "@/lib/api/http"
import type { Modul, Peran } from "@/lib/auth"
import type { Nada } from "@/lib/meta"

export type StatusAkun = "pending" | "active" | "suspended" | "deactivated"

export interface Akun {
  id: string
  nip: string
  nama: string
  surel: string | null
  peran: Peran
  status: StatusAkun
  /** Modul yang ditugaskan; Administrator selalu mengelola semua modul */
  modul: Modul[]
  dibuat: string
  diperbarui: string
}

/** Isian akun baru; surel boleh kosong */
export interface DataAkun {
  nip: string
  nama: string
  surel: string
  peran: Peran
  modul: Modul[]
}

/** Tautan aktivasi yang baru diterbitkan. Hanya ditampilkan sekali; server tidak menyimpannya. */
export interface TautanAktivasi {
  akun: Akun
  tautan: string
  kedaluwarsa: string
}

export const STATUS_AKUN: Record<StatusAkun, { label: string; nada: Nada; ikon: Icon }> = {
  pending: { label: "Menunggu aktivasi", nada: "proses", ikon: IconHourglassHigh },
  active: { label: "Aktif", nada: "sukses", ikon: IconCircleCheckFilled },
  suspended: { label: "Ditangguhkan", nada: "peringatan", ikon: IconPlayerPauseFilled },
  deactivated: { label: "Dinonaktifkan", nada: "netral", ikon: IconBan },
}

const KUNCI = ["akun"] as const
const url = (id?: string, sub = "") => `/api/akun${id ? `/${encodeURIComponent(id)}` : ""}${sub}`

export function useDaftarAkun() {
  return useQuery({
    queryKey: KUNCI,
    queryFn: () => mintaServer<Akun[]>(url(), "GET"),
    refetchOnWindowFocus: true,
  })
}

/** Ganti satu akun di daftar dengan data terbaru dari server */
function useTulisAkun() {
  const qc = useQueryClient()
  return (akun: Akun) => {
    qc.setQueryData<Akun[]>(KUNCI, (lama) => lama?.map((a) => (a.id === akun.id ? akun : a)))
  }
}

export function useTambahAkun() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: DataAkun) =>
      mintaServer<TautanAktivasi>(url(), "POST", { ...data, surel: data.surel.trim() || null }),
    onSuccess: () => void qc.invalidateQueries({ queryKey: KUNCI }),
  })
}

export function useUbahAkun() {
  const tulis = useTulisAkun()
  return useMutation({
    mutationFn: ({ id, data }: { id: string; data: Omit<DataAkun, "nip"> }) =>
      mintaServer<Akun>(url(id), "PATCH", { ...data, surel: data.surel.trim() || null }),
    onSuccess: tulis,
  })
}

export function useUbahStatusAkun() {
  const tulis = useTulisAkun()
  return useMutation({
    mutationFn: ({ id, status }: { id: string; status: Exclude<StatusAkun, "pending"> }) =>
      mintaServer<Akun>(url(id, "/status"), "PUT", { status }),
    onSuccess: tulis,
  })
}

export function useTerbitkanTautan() {
  const tulis = useTulisAkun()
  return useMutation({
    mutationFn: (id: string) => mintaServer<TautanAktivasi>(url(id, "/tautan-aktivasi"), "POST"),
    onSuccess: (hasil) => tulis(hasil.akun),
  })
}
