/*
 * Sesi login dashboard.
 *
 * Halaman Login Pegawai (/login) mengirim NIP dan kata sandi ke server
 * (POST /api/masuk). Server menyimpan sesi sebagai cookie Laravel Sanctum
 * (satu origin dengan dashboard), jadi dashboard cukup menanyakan siapa yang
 * sedang masuk (GET /api/sesi) dan meminta server mengakhiri sesi saat keluar.
 */
import { toast } from "sonner"

export type Peran = "administrator" | "pegawai"

/**
 * Modul dashboard yang ditugaskan Administrator kepada pegawai (D-010), dalam
 * urutan yang sama dengan DashboardModule di server. Nilainya nama koleksi atau
 * pengaturan utama modul.
 */
export const SEMUA_MODUL = [
  "pesan",
  "statistik",
  "penerimaan",
  "pemetaan",
  "berita",
  "dokumentasi",
  "dokumen",
  "faq",
  "layanan",
  "jam",
  "keliling",
  "papan",
  "antrean",
  "profil",
  "visiMisi",
  "unit",
  "kontak",
  "beranda",
  "halaman",
  "pemutakhiran",
] as const

export type Modul = (typeof SEMUA_MODUL)[number]

export interface Sesi {
  nip: string
  nama: string
  peran: Peran
  /** Modul yang boleh dikelola; Administrator selalu mendapat semuanya */
  modul: Modul[]
  /** Waktu login (ISO 8601); null bila belum tercatat di sesi ini */
  masuk: string | null
}

export const LABEL_PERAN: Record<Peran, string> = {
  administrator: "Administrator",
  pegawai: "Pegawai UPTD",
}

let sesiAktif: Sesi | null = null

/**
 * Opsi untuk permintaan ke server sendiri. Sanctum mengenali permintaan dari dashboard
 * lewat Referer, jadi Referer selalu dikirim untuk origin sendiri.
 */
export const SAMA_ORIGIN = { credentials: "same-origin", referrerPolicy: "same-origin" } as const satisfies RequestInit

/** Header CSRF untuk permintaan yang mengubah data, dari cookie XSRF-TOKEN milik Laravel */
export function headerXsrf(): Record<string, string> {
  const cocok = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/)
  return cocok ? { "X-XSRF-TOKEN": decodeURIComponent(cocok[1]) } : {}
}

/** Tanya server siapa yang sedang masuk; null bila belum atau tidak lagi login */
export async function muatSesi(): Promise<Sesi | null> {
  const res = await fetch("/api/sesi", { ...SAMA_ORIGIN, headers: { Accept: "application/json" } })
  if (res.status === 401) {
    sesiAktif = null
    return null
  }
  if (!res.ok) throw new Error(`Sesi tidak dapat diperiksa (${res.status}).`)
  sesiAktif = (await res.json()) as Sesi
  return sesiAktif
}

/** Sesi yang dimuat saat dashboard dibuka; main.tsx memuatnya sebelum aplikasi tampil */
export function sesi(): Sesi {
  if (!sesiAktif) throw new Error("Sesi login belum dimuat.")
  return sesiAktif
}

/**
 * Apakah pengguna ini mengelola salah satu modul tersebut. Untuk koleksi
 * yang sudah tersimpan di server (Tanya Jawab), server juga menolaknya;
 * untuk konten yang masih di peramban, ini hanya mengatur tampilan.
 */
export function bolehModul(...modul: Modul[]): boolean {
  const milik = sesi().modul
  return modul.some((m) => milik.includes(m))
}

/** URL halaman Login Pegawai di portal, relatif terhadap dashboard */
export function urlLogin(alasan?: "keluar" | "berakhir"): string {
  const url = new URL("../login", window.location.href)
  url.hash = ""
  if (alasan === "keluar") url.searchParams.set("keluar", "1")
  if (alasan === "berakhir") {
    url.searchParams.set("sesi", "berakhir")
    url.searchParams.set("lanjut", "dashboard/" + window.location.hash)
  }
  if (!alasan) url.searchParams.set("lanjut", "dashboard/" + window.location.hash)
  return url.toString()
}

/** Akhiri sesi di server lalu kembali ke halaman login */
export async function keluar() {
  try {
    await fetch("/sanctum/csrf-cookie", SAMA_ORIGIN)
    const res = await fetch("/api/keluar", {
      ...SAMA_ORIGIN,
      method: "POST",
      headers: { Accept: "application/json", ...headerXsrf() },
    })
    // 401: sesi sudah berakhir di server, jadi pengguna memang sudah keluar
    if (!res.ok && res.status !== 401) throw new Error(`Gagal keluar (${res.status}).`)
  } catch {
    toast.error("Gagal keluar", { description: "Server tidak dapat dihubungi. Coba lagi sebentar lagi." })
    return
  }
  sesiAktif = null
  window.location.replace(urlLogin("keluar"))
}
