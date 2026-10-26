/**
 * Pratinjau tulisan Berita & Pengumuman (D-086): tautan halaman tulisan di
 * portal untuk tulisan yang belum terbit, berlaku 24 jam bagi siapa pun yang
 * menerimanya. Hanya di server.
 */
import { useMutation } from "@tanstack/react-query"

import type { Id, IsoDateTime } from "@/lib/api"
import { mintaServer } from "@/lib/api/http"

export interface TautanPratinjau {
  tautan: string
  berlaku: IsoDateTime
}

export function useTautanPratinjau() {
  return useMutation({
    mutationFn: (id: Id) => mintaServer<TautanPratinjau>(`/api/koleksi/berita/${encodeURIComponent(id)}/pratinjau`, "POST"),
  })
}
