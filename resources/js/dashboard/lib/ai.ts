/**
 * Integrasi AI (D-085): pengaturan API untuk Administrator dan draf tulisan
 * Berita & Pengumuman. Hanya di server; kunci API tidak pernah sampai ke
 * peramban, dan dokumen sumber tidak disimpan.
 */
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query"

import type { DrafAi, HasilUjiAi, IntegrasiAi, KategoriBerita } from "@/lib/api"
import { mintaServer } from "@/lib/api/http"

const KUNCI = ["integrasi-ai"] as const

export interface NilaiIntegrasiAi {
  baseUrl: string
  model: string
  /** Kosong = pakai kunci yang tersimpan */
  kunci: string
  hapusKunci: boolean
}

export interface SumberDrafAi {
  tautan: string
  berkas: File[]
  /** Kosong = AI memilih kategori */
  kategori: KategoriBerita | ""
  petunjuk: string
}

/** Batas yang sama dengan server (GenerateArticleDraftRequest) */
export const BATAS_DRAF_AI = {
  berkas: 5,
  bait: 10 * 1024 * 1024,
  petunjuk: 500,
  ekstensi: [".pdf", ".docx", ".jpg", ".jpeg", ".png"],
} as const

export function useIntegrasiAi(aktif: boolean) {
  return useQuery({
    queryKey: KUNCI,
    queryFn: () => mintaServer<IntegrasiAi>("/api/pengaturan/ai", "GET"),
    enabled: aktif,
  })
}

export function useSimpanIntegrasiAi() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (nilai: NilaiIntegrasiAi) => mintaServer<IntegrasiAi>("/api/pengaturan/ai", "PUT", nilai),
    onSuccess: (data) => qc.setQueryData(KUNCI, data),
  })
}

/** Uji nilai di formulir, sudah disimpan atau belum */
export function useUjiIntegrasiAi() {
  return useMutation({
    mutationFn: ({ baseUrl, model, kunci }: Omit<NilaiIntegrasiAi, "hapusKunci">) =>
      mintaServer<HasilUjiAi>("/api/pengaturan/ai/uji", "POST", { baseUrl, model, kunci }),
  })
}

export function useBuatDrafAi() {
  return useMutation({
    mutationFn: ({ tautan, berkas, kategori, petunjuk }: SumberDrafAi) => {
      const form = new FormData()
      if (tautan.trim()) form.append("tautan", tautan.trim())
      for (const b of berkas) form.append("berkas[]", b, b.name)
      if (kategori) form.append("kategori", kategori)
      if (petunjuk.trim()) form.append("petunjuk", petunjuk.trim())
      return mintaServer<DrafAi>("/api/koleksi/berita/draf-ai", "POST", form)
    },
  })
}
