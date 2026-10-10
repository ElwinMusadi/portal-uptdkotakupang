import * as React from "react"

import type {
  Beralur,
  IsoDateTime,
  ModulPantau,
  Pemutakhiran,
  StatusAlur,
} from "@/lib/api"
import type { KoleksiBeralur } from "@/lib/alur"
import { selisihHari } from "@/lib/format"
import { useKoleksi, usePengaturan, useWaktuPengaturan } from "@/lib/queries"

export interface ItemAlur extends Beralur {
  id: string
  koleksi: KoleksiBeralur
  jenis: string
  judul: string
  diperbarui: IsoDateTime
  url: string
}

export const JENIS_KOLEKSI: Record<KoleksiBeralur, string> = {
  berita: "Berita",
  dokumentasi: "Dokumentasi",
  dokumen: "Unduhan",
  layanan: "Persyaratan",
}

/** Semua konten yang melewati alur publikasi, digabung dari empat koleksi */
export function useItemAlur() {
  const berita = useKoleksi("berita")
  const dokumentasi = useKoleksi("dokumentasi")
  const dokumen = useKoleksi("dokumen")
  const layanan = useKoleksi("layanan")

  const data = React.useMemo<ItemAlur[] | undefined>(() => {
    if (!berita.data || !dokumentasi.data || !dokumen.data || !layanan.data) return undefined
    const jenisBerita = (k: string) => (k === "pengumuman" ? "Pengumuman" : "Berita")
    return [
      ...berita.data.map((b) => ({ ...b, koleksi: "berita" as const, jenis: jenisBerita(b.kategori), url: `/berita/${b.id}` })),
      ...dokumentasi.data.map((d) => ({ ...d, koleksi: "dokumentasi" as const, jenis: "Dokumentasi", url: `/dokumentasi?ubah=${d.id}` })),
      ...dokumen.data.map((d) => ({ ...d, koleksi: "dokumen" as const, jenis: "Unduhan", url: `/unduhan?ubah=${d.id}` })),
      ...layanan.data.map((l) => ({ ...l, judul: l.nama, koleksi: "layanan" as const, jenis: "Persyaratan", url: `/layanan/${l.id}` })),
    ].sort((a, b) => b.diperbarui.localeCompare(a.diperbarui))
  }, [berita.data, dokumentasi.data, dokumen.data, layanan.data])

  return { data, memuat: !data }
}

/** Waktu sejak entri berada di status sekarang */
export function sejakStatus(item: { status: StatusAlur; riwayat: Beralur["riwayat"]; diperbarui: string }) {
  const r = item.riwayat.find((x) => x.ke === item.status)
  return r?.waktu ?? item.diperbarui
}

export type StatusPantau = "terkini" | "segera" | "jatuh-tempo"

export function nilaiPemutakhiran(p: Pemutakhiran, terakhir: string | undefined) {
  if (!terakhir) return { terakhir, sisa: -1, status: "jatuh-tempo" as StatusPantau }
  const umur = selisihHari(terakhir)
  const sisa = p.intervalHari - umur
  const status: StatusPantau =
    sisa < 0 ? "jatuh-tempo" : sisa <= Math.max(1, Math.round(p.intervalHari * 0.2)) ? "segera" : "terkini"
  return { terakhir, sisa, status }
}

/** Waktu pembaruan terakhir tiap modul yang dipantau */
export function useTerakhirModul(): Partial<Record<ModulPantau, string>> {
  const { data: waktu } = useWaktuPengaturan()
  const berita = useKoleksi("berita").data
  const dokumentasi = useKoleksi("dokumentasi").data
  const dokumen = useKoleksi("dokumen").data
  const layanan = useKoleksi("layanan").data
  const keliling = useKoleksi("keliling").data
  const papan = usePengaturan("papan").data

  return React.useMemo(() => {
    const maks = (arr?: { diperbarui: string }[]) =>
      arr?.reduce<string | undefined>((m, x) => (!m || x.diperbarui > m ? x.diperbarui : m), undefined)
    return {
      berita: maks(berita),
      dokumentasi: maks(dokumentasi),
      dokumen: maks(dokumen),
      layanan: maks(layanan),
      keliling: maks(keliling),
      jam: waktu?.jam,
      papan: papan?.diperbarui && waktu?.papan
        ? papan.diperbarui > waktu.papan ? papan.diperbarui : waktu.papan
        : (papan?.diperbarui ?? waktu?.papan),
      profil: waktu?.profil,
    }
  }, [berita, dokumentasi, dokumen, layanan, keliling, waktu, papan])
}

export function usePemutakhiran() {
  const { data } = useKoleksi("pemutakhiran")
  const terakhir = useTerakhirModul()
  return React.useMemo(
    () =>
      data?.map((p) => ({ ...p, ...nilaiPemutakhiran(p, terakhir[p.modul]) })) ?? undefined,
    [data, terakhir]
  )
}
