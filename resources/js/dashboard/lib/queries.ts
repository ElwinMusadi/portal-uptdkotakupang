import {
  QueryClient,
  useMutation,
  useQuery,
  useQueryClient,
} from "@tanstack/react-query"
import { toast } from "sonner"

import { api, ApiError } from "@/lib/api"
import type { Draf, Id, Koleksi, NamaKoleksi, NamaPengaturan, Pengaturan } from "@/lib/api"
import { keluar } from "@/lib/auth"

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 30_000,
      refetchOnWindowFocus: api.mode === "server",
      retry: (n, e) => !(e instanceof ApiError && [401, 403, 404].includes(e.status)) && n < 2,
    },
  },
})

export const qk = {
  koleksi: (k: NamaKoleksi) => ["koleksi", k] as const,
  pengaturan: (n: NamaPengaturan) => ["pengaturan", n] as const,
  waktuPengaturan: ["pengaturan-waktu"] as const,
  aktivitas: ["aktivitas"] as const,
}

/** Tampilkan galat sebagai toast; sesi kedaluwarsa diarahkan ke halaman login */
export function laporGalat(e: unknown, judul = "Gagal menyimpan") {
  if (e instanceof ApiError && e.status === 401) {
    toast.error(e.message)
    setTimeout(keluar, 1200)
    return
  }
  const pesan = e instanceof Error ? e.message : String(e)
  toast.error(judul, { description: pesan })
}

export function useKoleksi<K extends NamaKoleksi>(k: K) {
  return useQuery({ queryKey: qk.koleksi(k), queryFn: () => api.list(k) })
}

export function usePengaturan<N extends NamaPengaturan>(n: N) {
  return useQuery({ queryKey: qk.pengaturan(n), queryFn: () => api.getSetting(n) })
}

export function useWaktuPengaturan() {
  return useQuery({ queryKey: qk.waktuPengaturan, queryFn: () => api.settingsUpdatedAt() })
}

export function useAktivitas(limit = 50) {
  return useQuery({ queryKey: [...qk.aktivitas, limit], queryFn: () => api.activity(limit) })
}

function useSegarkan() {
  const qc = useQueryClient()
  return (k?: NamaKoleksi) => {
    if (k) void qc.invalidateQueries({ queryKey: qk.koleksi(k) })
    void qc.invalidateQueries({ queryKey: qk.aktivitas })
  }
}

/** Tulis entri hasil simpan langsung ke cache agar layar tidak menunggu muat ulang */
function useTulisCache<K extends NamaKoleksi>(k: K) {
  const qc = useQueryClient()
  return (entri: Koleksi[K]) =>
    qc.setQueryData<Koleksi[K][]>(qk.koleksi(k), (lama) =>
      lama ? [...lama.filter((x) => x.id !== entri.id), entri].sort((a, b) => a.urutan - b.urutan) : lama
    )
}

export function useTambahEntri<K extends NamaKoleksi>(k: K) {
  const segarkan = useSegarkan()
  const tulis = useTulisCache(k)
  return useMutation({
    mutationFn: (data: Draf<Koleksi[K]>) => api.create(k, data),
    onSuccess: (entri) => {
      tulis(entri)
      segarkan(k)
    },
    onError: (e) => laporGalat(e),
  })
}

export function useUbahEntri<K extends NamaKoleksi>(k: K) {
  const segarkan = useSegarkan()
  const tulis = useTulisCache(k)
  return useMutation({
    mutationFn: ({ id, patch }: { id: Id; patch: Partial<Koleksi[K]> }) => api.update(k, id, patch),
    onSuccess: (entri) => {
      tulis(entri)
      segarkan(k)
    },
    onError: (e) => laporGalat(e),
  })
}

export function useHapusEntri<K extends NamaKoleksi>(k: K) {
  const segarkan = useSegarkan()
  return useMutation({
    mutationFn: (ids: Id[]) => api.remove(k, ids),
    onSuccess: () => segarkan(k),
    onError: (e) => laporGalat(e, "Gagal menghapus"),
  })
}

/** Ubah urutan dengan pembaruan optimistis agar baris tidak "melompat" balik */
export function useUrutkanEntri<K extends NamaKoleksi>(k: K) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (ids: Id[]) => api.reorder(k, ids),
    onMutate: async (ids) => {
      await qc.cancelQueries({ queryKey: qk.koleksi(k) })
      const sebelum = qc.getQueryData<Koleksi[K][]>(qk.koleksi(k))
      if (sebelum) {
        const posisi = new Map(ids.map((id, i) => [id, i]))
        const urut = sebelum
          .filter((x) => posisi.has(x.id))
          .sort((a, b) => posisi.get(a.id)! - posisi.get(b.id)!)
        const lain = sebelum.filter((x) => !posisi.has(x.id))
        qc.setQueryData(
          qk.koleksi(k),
          [...urut, ...lain].map((x, i) => ({ ...x, urutan: i }))
        )
      }
      return { sebelum }
    },
    onError: (e, _ids, ctx) => {
      if (ctx?.sebelum) qc.setQueryData(qk.koleksi(k), ctx.sebelum)
      laporGalat(e, "Gagal mengubah urutan")
    },
    onSettled: () => {
      void qc.invalidateQueries({ queryKey: qk.koleksi(k) })
      void qc.invalidateQueries({ queryKey: qk.aktivitas })
    },
  })
}

export function useSimpanPengaturan<N extends NamaPengaturan>(n: N) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (nilai: Pengaturan[N]) => api.saveSetting(n, nilai),
    onSuccess: (nilai) => {
      qc.setQueryData(qk.pengaturan(n), nilai)
      void qc.invalidateQueries({ queryKey: qk.waktuPengaturan })
      void qc.invalidateQueries({ queryKey: qk.aktivitas })
    },
    onError: (e) => laporGalat(e),
  })
}
