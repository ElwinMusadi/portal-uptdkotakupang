/**
 * Pengaturan › Tautan Ikhtisar (D-099), khusus Administrator: tautan ke
 * halaman Ikhtisar (/ikhtisar) yang bisa dibuka tanpa login sampai dicabut.
 * Alamat tautan hanya dikembalikan sekali saat dibuat.
 */
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query"

import { mintaServer } from "@/lib/api/http"

const KUNCI = ["tautan-ikhtisar"] as const

export interface TautanIkhtisar {
  id: string
  nama: string
  terakhirDibuka: string | null
  dibuat: string | null
  dibuatOleh: string | null
}

export function useTautanIkhtisar() {
  return useQuery({
    queryKey: KUNCI,
    queryFn: () => mintaServer<TautanIkhtisar[]>("/api/pengaturan/ikhtisar", "GET"),
  })
}

/** Buat tautan baru; alamatnya hanya dikembalikan sekali */
export function useBuatTautanIkhtisar() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (nama: string) => mintaServer<{ tautan: TautanIkhtisar; alamat: string }>("/api/pengaturan/ikhtisar", "POST", { nama }),
    onSuccess: () => void qc.invalidateQueries({ queryKey: KUNCI }),
  })
}

export function useCabutTautanIkhtisar() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: string) => mintaServer<void>(`/api/pengaturan/ikhtisar/${encodeURIComponent(id)}`, "DELETE"),
    onSuccess: () => void qc.invalidateQueries({ queryKey: KUNCI }),
  })
}
