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
import { keepPreviousData, useMutation, useQuery, useQueryClient } from "@tanstack/react-query"

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

/** Asal NIP dan nama akun baru: dipilih dari SIKAP atau diketik sendiri (D-075) */
export type SumberAkun = "manual" | "sikap"

/** Isian akun baru; surel boleh kosong */
export interface DataAkun {
  nip: string
  nama: string
  surel: string
  peran: Peran
  modul: Modul[]
  /** Untuk "sikap", server mengambil ulang nama dari SIKAP dan mengabaikan nama dari peramban */
  sumber?: SumberAkun
}

/**
 * Pegawai aktif SIKAP untuk formulir Tambah akun (D-075). Akun hanya memakai
 * NIP dan nama; jabatan dan unit hanya ditampilkan di pemilih (D-027).
 */
export interface PegawaiSikap {
  nip: string
  nama: string
  jabatan: string | null
  unit: string | null
  /** NIP ini sudah punya akun PINTU, apa pun statusnya */
  sudahPunyaAkun: boolean
}

export interface HasilCariSikap {
  /** Paling banyak 20 pegawai, urut nama */
  pegawai: PegawaiSikap[]
  /** Jumlah semua pegawai yang cocok */
  total: number
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

/** Minimal karakter pencarian pegawai SIKAP, sama dengan server */
export const MIN_CARI_SIKAP = 2

/**
 * Cari pegawai SIKAP lewat server PINTU; kunci API SIKAP tidak pernah sampai
 * ke peramban. Pencarian tidak dicatat SIKAP, jadi aman dipanggil sambil mengetik.
 */
export function useCariPegawaiSikap(kataKunci: string) {
  const q = kataKunci.trim()
  return useQuery({
    queryKey: [...KUNCI, "pegawai-sikap", q],
    queryFn: () => mintaServer<HasilCariSikap>(`${url()}/pegawai-sikap?q=${encodeURIComponent(q)}`, "GET"),
    enabled: q.length >= MIN_CARI_SIKAP,
    placeholderData: keepPreviousData,
    staleTime: 60_000,
    refetchOnWindowFocus: false,
    retry: false,
  })
}

/**
 * Ambil satu pegawai SIKAP saat dipilih. SIKAP mencatat setiap pembacaan
 * ini di Log Aktivitasnya, jadi hanya dipanggil untuk pegawai yang dipilih.
 */
export function useAmbilPegawaiSikap() {
  return useMutation({
    mutationFn: (nip: string) => mintaServer<PegawaiSikap>(`${url()}/pegawai-sikap/${encodeURIComponent(nip)}`, "GET"),
  })
}

export function useTerbitkanTautan() {
  const tulis = useTulisAkun()
  return useMutation({
    mutationFn: (id: string) => mintaServer<TautanAktivasi>(url(id, "/tautan-aktivasi"), "POST"),
    onSuccess: (hasil) => tulis(hasil.akun),
  })
}
