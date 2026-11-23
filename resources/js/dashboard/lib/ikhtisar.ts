/**
 * Pengaturan › Halaman Ikhtisar (D-100), khusus Administrator: sakelar yang
 * membuka halaman Ikhtisar di /laporan-penerimaan untuk siapa saja, tanpa
 * login dan tanpa token, sampai dinonaktifkan lagi.
 */
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query"

import { mintaServer } from "@/lib/api/http"

const KUNCI = ["halaman-ikhtisar"] as const

export interface StatusIkhtisar {
  aktif: boolean
  /** Waktu terakhir diaktifkan atau dinonaktifkan; null bila belum pernah */
  sejak: string | null
  /** Alamat lengkap halaman, mis. https://uptdpenda-kupang.web.id/laporan-penerimaan */
  alamat: string
}

export function useStatusIkhtisar() {
  return useQuery({
    queryKey: KUNCI,
    queryFn: () => mintaServer<StatusIkhtisar>("/api/pengaturan/ikhtisar", "GET"),
  })
}

export function useSimpanStatusIkhtisar() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (aktif: boolean) => mintaServer<StatusIkhtisar>("/api/pengaturan/ikhtisar", "PUT", { aktif }),
    onSuccess: (data) => qc.setQueryData(KUNCI, data),
  })
}
