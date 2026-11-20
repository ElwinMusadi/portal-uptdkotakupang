/**
 * Pengaturan › Kuesioner (D-098): Administrator membuka dan menutup setiap
 * kuesioner di /kuesioner. Hanya di server; portal membaca statusnya lewat
 * GET /api/public/kuesioner.
 */
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query"

import type { JenisKuesioner, StatusKuesioner } from "@/lib/api"
import { mintaServer } from "@/lib/api/http"

const KUNCI = ["status-kuesioner"] as const

/** Urutan dan nama kuesioner, sama dengan halaman /kuesioner */
export const KUESIONER: { jenis: JenisKuesioner; judul: string; alamat: string }[] = [
  { jenis: "wajibPajak", judul: "Kuesioner Wajib Pajak", alamat: "/kuesioner/wajib-pajak" },
  { jenis: "operator", judul: "Kuesioner Petugas Operator", alamat: "/kuesioner/operator" },
]

export function useStatusKuesioner() {
  return useQuery({
    queryKey: KUNCI,
    queryFn: () => mintaServer<StatusKuesioner>("/api/pengaturan/kuesioner", "GET"),
  })
}

/** Simpan status kedua kuesioner; server mencatat sejak kapan status yang berubah berlaku. */
export function useSimpanStatusKuesioner() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (dibuka: Record<JenisKuesioner, boolean>) =>
      mintaServer<StatusKuesioner>("/api/pengaturan/kuesioner", "PUT", {
        wajibPajak: { dibuka: dibuka.wajibPajak },
        operator: { dibuka: dibuka.operator },
      }),
    onSuccess: (data) => qc.setQueryData(KUNCI, data),
  })
}
