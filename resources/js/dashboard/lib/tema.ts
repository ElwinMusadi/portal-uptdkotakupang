import * as React from "react"
import { keepPreviousData, useQuery } from "@tanstack/react-query"

import type {
  BagianTema,
  BagianTemaPortal,
  HurufTema,
  IsoDateTime,
  KacaNavbar,
  LatarTema,
  ModePortal,
  SkalaTema,
  SudutTema,
  TebalJudul,
  Tema,
} from "@/lib/api"
import { mintaServer } from "@/lib/api/http"

/** Tampilan bawaan PINTU (D-057), sama dengan Setting::DEFAULT_THEME_SECTION di server */
export const TEMA_BAWAAN: BagianTema = {
  aksen: "#2B63F6",
  latar: "hangat",
  hurufTeks: "sistem",
  hurufJudul: "sama",
  sudut: "sedang",
  skala: 100,
  tebalJudul: 600,
  gelap: "#0F1319",
}

/** Bagian portal tampilan bawaan: panel gelap hampir hitam, terang untuk semua pengunjung */
export const TEMA_BAWAAN_PORTAL: BagianTemaPortal = { ...TEMA_BAWAAN, panel: "#0A0A0C", navbar: "gelap", kaca: "mati", mode: "terang" }

/** contoh = [latar, teks] tiap mode, sama dengan tokens.css dan mode gelap di ThemeVariables */
export const MODE_PORTAL: { nilai: ModePortal; label: string; keterangan: string; contoh: [string, string] }[] = [
  { nilai: "terang", label: "Terang", keterangan: "Latar kertas, teks gelap (bawaan)", contoh: ["#F7F6F2", "#0F1319"] },
  { nilai: "gelap", label: "Gelap", keterangan: "Latar gelap, teks terang", contoh: ["#100F0D", "#EFEEEB"] },
]

/**
 * Huruf yang tersedia; DM Sans, Manrope, dan Lexend khusus judul (ThemeFont di
 * server): huruf teks perlu angka berlebar sama untuk tabel, dan berkas DM Sans
 * serta Lexend tidak memilikinya.
 * judul = ketebalan judul yang disarankan, yaitu yang setebal judul bawaan
 * (diukur dari tebal batang huruf terhadap Inter 600); maks = ketebalan
 * terbesar berkas hurufnya bila di bawah 800.
 */
export const HURUF: { nilai: HurufTema; label: string; keluarga: string | null; teks: boolean; judul: TebalJudul; maks?: TebalJudul }[] = [
  { nilai: "sistem", label: "Huruf sistem perangkat (bawaan)", keluarga: null, teks: true, judul: 600 },
  { nilai: "inter", label: "Inter", keluarga: "Inter", teks: true, judul: 600 },
  { nilai: "plus-jakarta-sans", label: "Plus Jakarta Sans", keluarga: "Plus Jakarta Sans", teks: true, judul: 700 },
  { nilai: "source-sans-3", label: "Source Sans 3", keluarga: "Source Sans 3", teks: true, judul: 600 },
  { nilai: "ibm-plex-sans", label: "IBM Plex Sans", keluarga: "IBM Plex Sans", teks: true, judul: 600, maks: 700 },
  { nilai: "nunito-sans", label: "Nunito Sans", keluarga: "Nunito Sans", teks: true, judul: 700 },
  { nilai: "dm-sans", label: "DM Sans (khusus judul)", keluarga: "DM Sans", teks: false, judul: 600 },
  { nilai: "manrope", label: "Manrope (khusus judul)", keluarga: "Manrope", teks: false, judul: 700 },
  { nilai: "lexend", label: "Lexend (khusus judul)", keluarga: "Lexend", teks: false, judul: 600 },
]

/** Huruf judul yang dipakai bagian ini: huruf teks bila judulnya "Sama" */
export function hurufJudulDipakai(bagian: Pick<BagianTema, "hurufTeks" | "hurufJudul">) {
  const nilai = bagian.hurufJudul === "sama" ? bagian.hurufTeks : bagian.hurufJudul
  return HURUF.find((h) => h.nilai === nilai) ?? HURUF[0]
}

/** font-family untuk contoh huruf; huruf sistem memakai tumpukan judul sistem seperti di server */
export function keluargaHuruf(keluarga: string | null) {
  const sistem = '-apple-system, BlinkMacSystemFont, system-ui, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif'
  return keluarga === null ? `"SF Pro Display", ${sistem}` : `"${keluarga}", ${sistem}`
}

/**
 * Contoh warna gelap siap klik; "Dari warna utama" memakai warna utama saat
 * ini, yang digelapkan server. Semua contoh lain sudah cukup gelap.
 */
export const GELAP: { nilai: string; label: string }[] = [
  { nilai: "#0F1319", label: "Hitam (bawaan)" },
  { nilai: "aksen", label: "Dari warna utama" },
  { nilai: "#0F1B3D", label: "Biru malam" },
  { nilai: "#0E2A20", label: "Hijau tua" },
  { nilai: "#2A1C14", label: "Cokelat kopi" },
  { nilai: "#24163A", label: "Ungu terong" },
]

/**
 * Contoh warna panel gelap; "gelap" memakai warna gelap tombol dan navigasi
 * dan "aksen" warna utama saat ini (keduanya dibuat segelap panel oleh
 * server). Contoh lain sudah segelap panel.
 */
export const PANEL: { nilai: string; label: string }[] = [
  { nilai: "#0A0A0C", label: "Hitam (bawaan)" },
  { nilai: "gelap", label: "Sama dengan warna gelap" },
  { nilai: "aksen", label: "Dari warna utama" },
  { nilai: "#0C1839", label: "Biru malam" },
  { nilai: "#042017", label: "Hijau tua" },
  { nilai: "#25170F", label: "Cokelat kopi" },
  { nilai: "#1F1135", label: "Ungu terong" },
]

/** Contoh warna navbar; "aksen" memakai warna utama saat ini */
export const NAVBAR: { nilai: string; label: string }[] = [
  { nilai: "#FFFFFF", label: "Putih" },
  { nilai: "#F7F6F2", label: "Kertas" },
  { nilai: "aksen", label: "Warna utama" },
  { nilai: "#0F1B3D", label: "Biru malam" },
  { nilai: "#0E2A20", label: "Hijau tua" },
  { nilai: "#0F1319", label: "Hitam" },
]

/** Tingkat efek glass navbar; server menaikkan tutupannya bila tautan navbar kurang terbaca */
export const KACA: { nilai: Exclude<KacaNavbar, "mati">; label: string; keterangan: string; tutupan: number }[] = [
  { nilai: "halus", label: "Halus", keterangan: "Sedikit tembus pandang", tutupan: 0.9 },
  { nilai: "sedang", label: "Sedang", keterangan: "Tembus pandang dengan blur", tutupan: 0.8 },
  { nilai: "kuat", label: "Kuat", keterangan: "Paling tembus pandang", tutupan: 0.68 },
]

export const TEBAL_JUDUL: { nilai: TebalJudul; label: string }[] = [
  { nilai: 600, label: "Semi-tebal" },
  { nilai: 700, label: "Tebal" },
  { nilai: 800, label: "Ekstra tebal" },
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
  /**
   * Tanpa skala: memilih tema siap pakai tidak mengubah ukuran tampilan.
   * Tanpa ketebalan judul: diisi saran untuk huruf judulnya. Tanpa warna
   * gelap: tema siap pakai tidak mengubahnya.
   */
  nilai: Omit<BagianTema, "skala" | "tebalJudul" | "gelap">
}

/** Tema siap pakai; tiap pilihan tetap bisa diubah sesudahnya (menjadi "Kustom") */
export const TEMA_SIAP_PAKAI: TemaSiapPakai[] = [
  {
    id: "pintu",
    nama: "PINTU",
    keterangan: "Biru PINTU, kertas hangat (bawaan)",
    nilai: {
      aksen: TEMA_BAWAAN.aksen,
      latar: TEMA_BAWAAN.latar,
      hurufTeks: TEMA_BAWAAN.hurufTeks,
      hurufJudul: TEMA_BAWAAN.hurufJudul,
      sudut: TEMA_BAWAAN.sudut,
    },
  },
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
    keterangan: "Jingga bata, sudut membulat, Nunito Sans, judul DM Sans",
    nilai: { aksen: "#C2410C", latar: "hangat", hurufTeks: "nunito-sans", hurufJudul: "dm-sans", sudut: "membulat" },
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

/** Tema siap pakai yang sama dengan pilihan sekarang (skala dan ketebalan judul tidak dihitung), bila ada */
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
    /** Warna gelap tombol dan navigasi mode terang sesudah disesuaikan */
    gelap: string
    /** Warna panel gelap (--night) sesudah disesuaikan */
    panel: string
    /** Warna navbar sendiri sesudah disesuaikan; null bila mengikuti warna gelap */
    navbar: string | null
    /** Tutupan navbar glass yang dipakai (0–1) sesudah dinaikkan agar tautan terbaca; null tanpa glass */
    tutupanKaca: number | null
    kontras: Kontras[]
  }
  /** aksen = warna merek dashboard dan gelap = tombol utama, sesudah disesuaikan agar terbaca */
  dashboard: { aksen: string; gelap: string; css: string; kontras: Kontras[] }
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
    enabled:
      aktif &&
      [isi.portal.aksen, isi.portal.gelap, isi.portal.panel, isi.dashboard.aksen, isi.dashboard.gelap].every((w) => POLA_WARNA.test(w)) &&
      (isi.portal.navbar === "gelap" || POLA_WARNA.test(isi.portal.navbar)),
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
