/*
 * Sesi login dashboard.
 *
 * Halaman Login Pegawai (../login.html) memeriksa NIP & kata sandi lalu
 * menyimpan sesi dengan format di bawah. Dashboard hanya membaca sesi itu.
 * Format ini harus sama dengan yang ditulis assets/js/main.js di portal.
 *
 * PERHATIAN: kredensial statis + sesi di peramban hanya untuk prototipe.
 * Sebelum dipakai publik, ganti dengan autentikasi di server (token dari API).
 */

export const KUNCI_SESI = "pintu.sesi"

export interface Sesi {
  nip: string
  masuk: string
  kedaluwarsa: string
  ingat: boolean
  /** Token dari server bila sudah memakai REST API */
  token?: string
}

function baca(ambil: () => Storage): Sesi | null {
  try {
    const storage = ambil()
    const raw = storage.getItem(KUNCI_SESI)
    if (!raw) return null
    const sesi = JSON.parse(raw) as Partial<Sesi>
    if (typeof sesi.nip !== "string" || typeof sesi.kedaluwarsa !== "string") return null
    if (!(Date.parse(sesi.kedaluwarsa) > Date.now())) {
      storage.removeItem(KUNCI_SESI)
      return null
    }
    return sesi as Sesi
  } catch {
    return null
  }
}

export function bacaSesi(): Sesi | null {
  return baca(() => window.sessionStorage) ?? baca(() => window.localStorage)
}

export function hapusSesi() {
  for (const ambil of [() => window.localStorage, () => window.sessionStorage]) {
    try {
      ambil().removeItem(KUNCI_SESI)
    } catch {
      // penyimpanan diblokir; tidak ada yang perlu dihapus
    }
  }
}

/** URL halaman Login Pegawai di portal, relatif terhadap folder dashboard */
export function urlLogin(alasan?: "keluar" | "berakhir"): string {
  const url = new URL("../login.html", window.location.href)
  url.hash = ""
  if (alasan === "keluar") url.searchParams.set("keluar", "1")
  if (alasan === "berakhir") {
    url.searchParams.set("sesi", "berakhir")
    url.searchParams.set("lanjut", "dashboard/" + window.location.hash)
  }
  if (!alasan) url.searchParams.set("lanjut", "dashboard/" + window.location.hash)
  return url.toString()
}

export function keluar() {
  hapusSesi()
  window.location.replace(urlLogin("keluar"))
}
