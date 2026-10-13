import * as React from "react"

import type {
  Beralur,
  IsoDateTime,
  Layanan,
  ModulPantau,
  Pemutakhiran,
  StatusAlur,
} from "@/lib/api"
import type { KoleksiBeralur } from "@/lib/alur"
import { bolehModul, sesi } from "@/lib/auth"
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

/** Pengganti daftar layanan bagi yang tidak boleh membacanya; tetap sama antar-render */
const TANPA_LAYANAN: Layanan[] = []

/** Konten yang melewati alur publikasi dari empat koleksi, terbatas pada modul pengguna */
export function useItemAlur() {
  const berita = useKoleksi("berita")
  const dokumentasi = useKoleksi("dokumentasi")
  const dokumen = useKoleksi("dokumen")
  // Di server hanya pengelola Persyaratan Layanan yang boleh membacanya (alurnya tidak tampil bagi yang lain)
  const bacaLayanan = bolehModul("layanan")
  const layanan = useKoleksi("layanan", { aktif: bacaLayanan })
  const dataLayanan = bacaLayanan ? layanan.data : TANPA_LAYANAN

  const data = React.useMemo<ItemAlur[] | undefined>(() => {
    if (!berita.data || !dokumentasi.data || !dokumen.data || !dataLayanan) return undefined
    const jenisBerita = (k: string) => (k === "pengumuman" ? "Pengumuman" : "Berita")
    const modul = sesi().modul
    return [
      ...berita.data.map((b) => ({ ...b, koleksi: "berita" as const, jenis: jenisBerita(b.kategori), url: `/berita/${b.id}` })),
      ...dokumentasi.data.map((d) => ({ ...d, koleksi: "dokumentasi" as const, jenis: "Dokumentasi", url: `/dokumentasi?ubah=${d.id}` })),
      ...dokumen.data.map((d) => ({ ...d, koleksi: "dokumen" as const, jenis: "Unduhan", url: `/unduhan?ubah=${d.id}` })),
      ...dataLayanan.map((l) => ({ ...l, judul: l.nama, koleksi: "layanan" as const, jenis: "Persyaratan", url: `/layanan/${l.id}` })),
    ]
      .filter((x) => modul.includes(x.koleksi))
      .sort((a, b) => b.diperbarui.localeCompare(a.diperbarui))
  }, [berita.data, dokumentasi.data, dokumen.data, dataLayanan])

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
  // Di server: hanya pengelola Persyaratan Layanan dan Jadwal Pemutakhiran yang boleh membacanya.
  const layanan = useKoleksi("layanan", { aktif: bolehModul("layanan", "pemutakhiran") }).data
  // Di server: hanya pengelola Samsat Keliling dan Jadwal Pemutakhiran yang boleh membacanya.
  const keliling = useKoleksi("keliling", { aktif: bolehModul("keliling", "pemutakhiran") }).data
  // Di server hanya pengelola Papan Layanan yang boleh membacanya; yang lain memakai waktu simpannya.
  const papan = usePengaturan("papan", { aktif: bolehModul("papan") }).data

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

/**
 * Jadwal pemutakhiran. Pengelola modul Jadwal Pemutakhiran memantau semua modul;
 * pegawai lain hanya melihat jadwal modul yang mereka kelola.
 */
export function usePemutakhiran() {
  const { data } = useKoleksi("pemutakhiran")
  const terakhir = useTerakhirModul()
  return React.useMemo(() => {
    const modul = sesi().modul
    return data
      ?.filter((p) => modul.includes("pemutakhiran") || modul.includes(p.modul))
      .map((p) => ({ ...p, ...nilaiPemutakhiran(p, terakhir[p.modul]) }))
  }, [data, terakhir])
}
