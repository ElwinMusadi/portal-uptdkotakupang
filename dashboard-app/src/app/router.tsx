import { IconRefresh } from "@tabler/icons-react"
import { createHashRouter, isRouteErrorResponse, useRouteError, type RouteObject } from "react-router"

import { Layout } from "@/app/layout"
import { Button } from "@/components/ui/button"

type ModulHalaman = { default: React.ComponentType }

/** Rute dengan pemisahan kode: setiap halaman dimuat saat dibuka */
function halaman(judul: string, muat: () => Promise<ModulHalaman>): Pick<RouteObject, "lazy" | "handle"> {
  return {
    handle: { judul },
    lazy: async () => ({ Component: (await muat()).default }),
  }
}

function MemuatAwal() {
  return (
    <div className="flex min-h-svh items-center justify-center" role="status">
      <span className="sr-only">Memuat dashboard…</span>
      <img src="./brand/lambang-ntt-96.webp" alt="" width={44} height={47} className="h-[47px] w-auto animate-pulse" />
    </div>
  )
}

function GalatRute() {
  const galat = useRouteError()
  const pesan = isRouteErrorResponse(galat)
    ? `${galat.status} ${galat.statusText}`
    : galat instanceof Error
      ? galat.message
      : "Terjadi kesalahan yang tidak terduga."
  const muatUlang = /dynamically imported module|Failed to fetch|Importing a module script failed/i.test(pesan)
  return (
    <div className="flex min-h-svh items-center justify-center p-6">
      <div className="grid max-w-md gap-3 text-center">
        <h1 className="text-xl font-semibold">Halaman gagal ditampilkan</h1>
        <p className="text-sm text-muted-foreground">
          {muatUlang
            ? "Dashboard baru saja diperbarui. Muat ulang halaman untuk memakai versi terbaru."
            : pesan}
        </p>
        <div>
          <Button onClick={() => window.location.reload()}>
            <IconRefresh />
            Muat ulang
          </Button>
        </div>
      </div>
    </div>
  )
}

export const router = createHashRouter([
  {
    path: "/",
    element: <Layout />,
    errorElement: <GalatRute />,
    HydrateFallback: MemuatAwal,
    children: [
      { index: true, ...halaman("Ringkasan", () => import("@/pages/ringkasan")) },
      { path: "alur", ...halaman("Alur Publikasi", () => import("@/pages/alur")) },
      { path: "pesan", ...halaman("Pesan & Pengaduan", () => import("@/pages/pesan")) },
      { path: "statistik", ...halaman("Statistik Layanan", () => import("@/pages/statistik")) },
      { path: "berita", ...halaman("Berita & Pengumuman", () => import("@/pages/berita/daftar")) },
      { path: "berita/baru", ...halaman("Tulis berita", () => import("@/pages/berita/editor")) },
      { path: "berita/:id", ...halaman("Sunting berita", () => import("@/pages/berita/editor")) },
      { path: "dokumentasi", ...halaman("Dokumentasi Kegiatan", () => import("@/pages/dokumentasi")) },
      { path: "unduhan", ...halaman("Regulasi & Unduhan", () => import("@/pages/unduhan")) },
      { path: "faq", ...halaman("Tanya Jawab", () => import("@/pages/faq")) },
      { path: "layanan", ...halaman("Persyaratan Layanan", () => import("@/pages/layanan/daftar")) },
      { path: "layanan/baru", ...halaman("Tambah layanan", () => import("@/pages/layanan/editor")) },
      { path: "layanan/:id", ...halaman("Sunting layanan", () => import("@/pages/layanan/editor")) },
      { path: "jadwal/jam", ...halaman("Jam Pelayanan", () => import("@/pages/jadwal/jam")) },
      { path: "jadwal/keliling", ...halaman("Samsat Keliling", () => import("@/pages/jadwal/keliling")) },
      { path: "papan", ...halaman("Papan Layanan", () => import("@/pages/papan")) },
      { path: "tarif", ...halaman("Tarif & Simulasi", () => import("@/pages/tarif")) },
      { path: "profil", ...halaman("Tentang UPTD", () => import("@/pages/profil")) },
      { path: "visi-misi", ...halaman("Visi & Misi", () => import("@/pages/visi-misi")) },
      { path: "struktur", ...halaman("Struktur Organisasi", () => import("@/pages/struktur")) },
      { path: "kontak", ...halaman("Kontak & Kanal Pengaduan", () => import("@/pages/kontak")) },
      { path: "beranda", ...halaman("Beranda", () => import("@/pages/beranda")) },
      { path: "halaman", ...halaman("Halaman Statis", () => import("@/pages/halaman/daftar")) },
      { path: "halaman/baru", ...halaman("Tambah halaman", () => import("@/pages/halaman/editor")) },
      { path: "halaman/:id", ...halaman("Sunting halaman", () => import("@/pages/halaman/editor")) },
      { path: "pemutakhiran", ...halaman("Jadwal Pemutakhiran", () => import("@/pages/pemutakhiran")) },
      { path: "absensi", ...halaman("Absensi Lapangan", () => import("@/pages/absensi")) },
      { path: "kalkulator", ...halaman("Kalkulator PKB", () => import("@/pages/kalkulator")) },
      { path: "pengaturan", ...halaman("Pengaturan", () => import("@/pages/pengaturan")) },
      { path: "panduan", ...halaman("Panduan", () => import("@/pages/panduan")) },
      { path: "*", ...halaman("Halaman tidak ditemukan", () => import("@/pages/tidak-ditemukan")) },
    ],
  },
])
