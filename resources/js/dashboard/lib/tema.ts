import * as React from "react"
import { keepPreviousData, useQuery } from "@tanstack/react-query"

import type { BagianTema, BagianTemaPortal, HurufTema, IsoDateTime, LatarTema, ModePortal, SkalaTema, SudutTema, Tema } from "@/lib/api"
import { mintaServer } from "@/lib/api/http"

/** Tampilan bawaan PINTU (D-057), sama dengan Setting::DEFAULT_THEME_SECTION di server */
export const TEMA_BAWAAN: BagianTema = {
  aksen: "#2B63F6",
  latar: "hangat",
  hurufTeks: "sistem",
  hurufJudul: "sama",
  sudut: "sedang",
  skala: 100,
}

/** Bagian portal tampilan bawaan: terang untuk semua pengunjung */
export const TEMA_BAWAAN_PORTAL: BagianTemaPortal = { ...TEMA_BAWAAN, mode: "terang" }

/** contoh = [latar, teks] tiap mode, sama dengan tokens.css dan mode gelap di ThemeVariables */
export const MODE_PORTAL: { nilai: ModePortal; label: string; keterangan: string; contoh: [string, string] }[] = [
  { nilai: "terang", label: "Terang", keterangan: "Latar kertas, teks gelap (bawaan)", contoh: ["#F7F6F2", "#0F1319"] },
  { nilai: "gelap", label: "Gelap", keterangan: "Latar gelap, teks terang", contoh: ["#100F0D", "#EFEEEB"] },
]

/** Huruf yang tersedia; Manrope dan Lexend khusus judul (ThemeFont di server) */
export const HURUF: { nilai: HurufTema; label: string; teks: boolean }[] = [
  { nilai: "sistem", label: "Huruf sistem perangkat (bawaan)", teks: true },
  { nilai: "inter", label: "Inter", teks: true },
  { nilai: "plus-jakarta-sans", label: "Plus Jakarta Sans", teks: true },
  { nilai: "dm-sans", label: "DM Sans", teks: true },
  { nilai: "source-sans-3", label: "Source Sans 3", teks: true },
  { nilai: "ibm-plex-sans", label: "IBM Plex Sans", teks: true },
  { nilai: "nunito-sans", label: "Nunito Sans", teks: true },
  { nilai: "manrope", label: "Manrope (khusus judul)", teks: false },
  { nilai: "lexend", label: "Lexend (khusus judul)", teks: false },
]

export const LATAR: { nilai: LatarTema; label: string; keterangan: string; contoh: [string, string] }[] = [
  { nilai: "hangat", label: "Hangat", keterangan: "Krem seperti kertas (bawaan)", contoh: ["#F7F6F2", "#EFEDE6"] },
  { nilai: "netral", label: "Netral", keterangan: "Abu-abu putih", contoh: ["#F6F6F6", "#EDEDED"] },
  { nilai: "sejuk", label: "Sejuk", keterangan: "Abu kebiruan", contoh: ["#F4F6FA", "#E9EDF4"] },
]

export const SUDUT: { nilai: SudutTema; label: string; keterangan: string; px: number }[] = [
  { nilai: "tegas", label: "Tegas", keterangan: "Separuh lengkungan bawaan", px: 6 },
  { nilai: "sedang", label: "Sedang", keterangan: "Lengkungan bawaan", px: 12 },
  { nilai: "membulat", label: "Membulat", keterangan: "Sepertiga lebih bulat", px: 16 },
]

export const SKALA: { nilai: SkalaTema; label: string; keterangan: string }[] = [
  { nilai: 90, label: "90%", keterangan: "Lebih kecil" },
  { nilai: 100, label: "100%", keterangan: "Bawaan" },
  { nilai: 110, label: "110%", keterangan: "Lebih besar" },
  { nilai: 120, label: "120%", keterangan: "Paling besar" },
]

export interface TemaSiapPakai {
  id: string
  nama: string
  keterangan: string
  /** Tanpa skala: memilih tema siap pakai tidak mengubah ukuran tampilan */
  nilai: Omit<BagianTema, "skala">
}

/** Tema siap pakai; tiap pilihan tetap bisa diubah sesudahnya (menjadi "Kustom") */
export const TEMA_SIAP_PAKAI: TemaSiapPakai[] = [
  { id: "pintu", nama: "PINTU", keterangan: "Biru PINTU, kertas hangat (bawaan)", nilai: TEMA_BAWAAN },
  {
    id: "samudra",
    nama: "Samudra",
    keterangan: "Biru laut, latar sejuk, Inter",
    nilai: { aksen: "#0E7490", latar: "sejuk", hurufTeks: "inter", hurufJudul: "sama", sudut: "sedang" },
  },
  {
    id: "hutan",
    nama: "Hutan",
    keterangan: "Hijau, latar netral, Source Sans 3",
    nilai: { aksen: "#2F7D4F", latar: "netral", hurufTeks: "source-sans-3", hurufJudul: "sama", sudut: "sedang" },
  },
  {
    id: "senja",
    nama: "Senja",
    keterangan: "Jingga bata, sudut membulat, DM Sans",
    nilai: { aksen: "#C2410C", latar: "hangat", hurufTeks: "dm-sans", hurufJudul: "sama", sudut: "membulat" },
  },
  {
    id: "tenun",
    nama: "Tenun",
    keterangan: "Merah marun khas NTT, judul Lexend",
    nilai: { aksen: "#9F1239", latar: "hangat", hurufTeks: "plus-jakarta-sans", hurufJudul: "lexend", sudut: "sedang" },
  },
  {
    id: "grafit",
    nama: "Grafit",
    keterangan: "Abu netral, sudut tegas, IBM Plex Sans",
    nilai: { aksen: "#3F4652", latar: "netral", hurufTeks: "ibm-plex-sans", hurufJudul: "sama", sudut: "tegas" },
  },
]

/** Tema siap pakai yang sama dengan pilihan sekarang (skala tidak dihitung), bila ada */
export function temaSiapPakai(bagian: BagianTema): TemaSiapPakai | undefined {
  return TEMA_SIAP_PAKAI.find(
    (t) =>
      t.nilai.aksen.toUpperCase() === bagian.aksen.toUpperCase() &&
      t.nilai.latar === bagian.latar &&
      t.nilai.hurufTeks === bagian.hurufTeks &&
      t.nilai.hurufJudul === bagian.hurufJudul &&
      t.nilai.sudut === bagian.sudut
  )
}

export const POLA_WARNA = /^#[0-9a-f]{6}$/i

export interface Kontras {
  pasangan: string
  rasio: number
}

/** Tema yang belum disimpan sebagaimana akan diterapkan (POST /api/pengaturan/tema/pratinjau) */
export interface PratinjauTema {
  portal: {
    /** Semua variabel portal yang bisa diatur tema, dengan nilai pilihan ini */
    variabel: Record<string, string>
    /** Aturan @font-face huruf portal yang dipilih */
    huruf: string
    kontras: Kontras[]
  }
  /** aksen = warna merek dashboard sesudah disesuaikan agar terbaca */
  dashboard: { aksen: string; css: string; kontras: Kontras[] }
}

/** Pratinjau tema dari server, ditunda sebentar selama warna diketik atau digeser */
export function usePratinjauTema(tema: Tema, aktif: boolean) {
  const kunci = JSON.stringify(tema)
  const [tertunda, setTertunda] = React.useState(kunci)
  React.useEffect(() => {
    const t = window.setTimeout(() => setTertunda(kunci), 250)
    return () => window.clearTimeout(t)
  }, [kunci])
  const isi = JSON.parse(tertunda) as Tema
  return useQuery({
    queryKey: ["tema-pratinjau", tertunda],
    queryFn: () => mintaServer<PratinjauTema>("/api/pengaturan/tema/pratinjau", "POST", isi),
    enabled: aktif && POLA_WARNA.test(isi.portal.aksen) && POLA_WARNA.test(isi.dashboard.aksen),
    placeholderData: keepPreviousData,
    staleTime: Infinity,
    retry: false,
  })
}

const ID_PRATINJAU = "tema-pratinjau-dashboard"
const ID_HURUF_PORTAL = "tema-pratinjau-huruf-portal"

function tautanTema(): HTMLLinkElement | null {
  return document.querySelector<HTMLLinkElement>('link[rel="stylesheet"][href*="tema-dashboard.css"]')
}

function gaya(id: string): HTMLStyleElement {
  let el = document.getElementById(id) as HTMLStyleElement | null
  if (!el) {
    el = document.createElement("style")
    el.id = id
    document.head.appendChild(el)
  }
  return el
}

/**
 * Terapkan tema dashboard yang sedang disunting ke dashboard ini sebagai
 * pratinjau, menggantikan tema tersimpan; null mengembalikan tema tersimpan.
 */
export function pratinjauDashboard(css: string | null) {
  const tautan = tautanTema()
  if (css === null) {
    document.getElementById(ID_PRATINJAU)?.remove()
    if (tautan) tautan.disabled = false
    return
  }
  gaya(ID_PRATINJAU).textContent = css
  if (tautan) tautan.disabled = true
}

/** Muat huruf portal yang dipilih agar contoh portal tampil dengan hurufnya; null melepasnya */
export function hurufPratinjauPortal(css: string | null) {
  if (css === null) document.getElementById(ID_HURUF_PORTAL)?.remove()
  else gaya(ID_HURUF_PORTAL).textContent = css
}

/**
 * Ambil ulang /tema-dashboard.css sesudah tema disimpan, tanpa memuat ulang
 * halaman. Tautan lama dilepas setelah yang baru termuat; selama pratinjau
 * masih tampil, tautan baru dinonaktifkan seperti yang lama.
 */
export function muatUlangTemaDashboard() {
  const tautan = tautanTema()
  if (!tautan) return
  const alamat = new URL(tautan.href, window.location.href)
  alamat.searchParams.set("v", String(Date.now()))
  const baru = tautan.cloneNode() as HTMLLinkElement
  baru.disabled = false
  baru.href = alamat.toString()
  baru.onload = () => {
    tautan.remove()
    baru.disabled = document.getElementById(ID_PRATINJAU) !== null
  }
  baru.onerror = () => baru.remove()
  tautan.after(baru)
}

/** Tema yang pernah disimpan (GET /api/pengaturan/tema/riwayat), terbaru dulu; yang pertama sedang dipakai */
export interface RiwayatTema {
  id: string
  tema: Tema
  disimpan: IsoDateTime
  /** Nama Administrator yang menyimpan; null bila akunnya sudah dihapus */
  oleh: string | null
}

export function useRiwayatTema(aktif: boolean) {
  return useQuery({
    queryKey: ["tema-riwayat"],
    queryFn: () => mintaServer<RiwayatTema[]>("/api/pengaturan/tema/riwayat", "GET"),
    enabled: aktif,
  })
}

/** Tautan yang menampilkan tema portal yang belum disimpan di portal itu sendiri, hanya di peramban ini */
export function buatTautanPratinjau(tema: Tema) {
  return mintaServer<{ tautan: string; berlaku: IsoDateTime }>("/api/pengaturan/tema/tautan-pratinjau", "POST", tema)
}

/** Akhiri pratinjau tema di portal pada peramban ini */
export async function akhiriPratinjauPortal() {
  await fetch("/pratinjau-tema/selesai", { credentials: "same-origin", redirect: "manual" })
}

/** Rasio kontras seperti "4,98 : 1" */
export function rasio(nilai: number) {
  return `${nilai.toLocaleString("id-ID", { minimumFractionDigits: 2, maximumFractionDigits: 2 })} : 1`
}
