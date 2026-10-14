import * as React from "react"

/*
 * Lokasi, kamera, dan waktu untuk aplikasi Absensi PINTU (D-034). Keduanya
 * hanya bisa dipakai lewat koneksi aman (HTTPS); server tetap yang
 * memutuskan jarak, akurasi, dan waktu clock-in.
 */

export interface Titik {
  lat: number
  lng: number
  /** Perkiraan akurasi dalam meter */
  akurasi: number
}

export type StatusLokasi =
  | { status: "mencari" }
  | { status: "ada"; titik: Titik }
  | { status: "galat"; pesan: string }

/** Batas akurasi GPS yang diterima server, dalam meter */
export const AKURASI_MAKS = 100

/** Jarak dua titik di permukaan bumi dalam meter (haversine), sama dengan perhitungan server */
export function jarakMeter(a: { lat: number; lng: number }, b: { lat: number; lng: number }): number {
  const rad = (d: number) => (d * Math.PI) / 180
  const dLat = rad(b.lat - a.lat)
  const dLng = rad(b.lng - a.lng)
  const h = Math.sin(dLat / 2) ** 2 + Math.cos(rad(a.lat)) * Math.cos(rad(b.lat)) * Math.sin(dLng / 2) ** 2
  return Math.round(2 * 6371000 * Math.asin(Math.min(1, Math.sqrt(h))))
}

/** Minta titik GPS saat dipasang; `cari` untuk memperbarui */
export function useLokasi() {
  const [st, setSt] = React.useState<StatusLokasi>({ status: "mencari" })
  const nomor = React.useRef(0)

  const cari = React.useCallback(() => {
    const ke = ++nomor.current
    if (!window.isSecureContext || !("geolocation" in navigator)) {
      setSt({ status: "galat", pesan: "Lokasi GPS hanya bisa dibaca lewat koneksi aman (HTTPS) di peramban yang mendukungnya." })
      return
    }
    setSt({ status: "mencari" })
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        if (ke !== nomor.current) return
        setSt({
          status: "ada",
          titik: { lat: pos.coords.latitude, lng: pos.coords.longitude, akurasi: Math.ceil(pos.coords.accuracy) },
        })
      },
      (galat) => {
        if (ke !== nomor.current) return
        setSt({
          status: "galat",
          pesan:
            galat.code === galat.PERMISSION_DENIED
              ? "Izin lokasi ditolak. Izinkan akses lokasi untuk aplikasi ini di pengaturan peramban, lalu coba lagi."
              : galat.code === galat.TIMEOUT
                ? "Lokasi belum ditemukan. Pastikan GPS aktif dan coba di area terbuka."
                : "Lokasi tidak tersedia. Pastikan GPS aktif, lalu coba lagi.",
        })
      },
      { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 }
    )
  }, [])

  React.useEffect(() => {
    cari()
    return () => {
      // abaikan jawaban yang datang setelah layar ditutup
      nomor.current++
    }
  }, [cari])

  return { st, cari }
}

/** Pesan galat kamera yang dapat dimengerti petugas */
export function pesanGalatKamera(galat: unknown): string {
  const nama = galat instanceof DOMException ? galat.name : ""
  if (nama === "NotAllowedError") return "Izin kamera ditolak. Izinkan kamera untuk aplikasi ini di pengaturan peramban, lalu coba lagi."
  if (nama === "NotFoundError" || nama === "OverconstrainedError") return "Kamera depan tidak ditemukan di perangkat ini."
  if (nama === "NotReadableError") return "Kamera sedang dipakai aplikasi lain. Tutup aplikasi itu lalu coba lagi."
  return "Kamera tidak dapat dibuka."
}

const WITA = "Asia/Makassar"

/** Jam WITA, mis. "07.42" */
export function jamWita(iso: string): string {
  return new Intl.DateTimeFormat("id-ID", { timeZone: WITA, hour: "2-digit", minute: "2-digit", hourCycle: "h23" })
    .format(new Date(iso))
    .replace(":", ".")
}

/** Tanggal panjang dari "YYYY-MM-DD", mis. "Rabu, 14 Oktober 2026" */
export function tanggalPanjang(tanggal: string): string {
  const [y, m, d] = tanggal.split("-").map(Number)
  return new Intl.DateTimeFormat("id-ID", { timeZone: "UTC", weekday: "long", day: "numeric", month: "long", year: "numeric" }).format(
    new Date(Date.UTC(y, m - 1, d))
  )
}
