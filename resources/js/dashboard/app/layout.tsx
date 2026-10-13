import * as React from "react"
import { IconAlertTriangle, IconLock } from "@tabler/icons-react"
import { Link, Outlet, ScrollRestoration, useLocation } from "react-router"

import { bolehDilihat, SEMUA_NAV, type ItemNav } from "@/app/nav"
import { AppSidebar } from "@/components/app-sidebar"
import { CommandMenuProvider } from "@/components/command-menu"
import { KonfirmasiProvider } from "@/components/data/confirm"
import { KerangkaMemuat } from "@/components/form/settings-form"
import { ruteAktif } from "@/components/nav-main"
import { SiteHeader } from "@/components/site-header"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Button } from "@/components/ui/button"
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from "@/components/ui/empty"
import { SidebarInset, SidebarProvider } from "@/components/ui/sidebar"
import { api } from "@/lib/api"
import { muatSesi, sesi, urlLogin } from "@/lib/auth"
import { aturBasisPortal } from "@/lib/portal"
import { usePengaturan } from "@/lib/queries"

/** Item menu halaman ini bila pengguna tidak boleh membukanya (modul tidak ditugaskan atau khusus Administrator) */
function useHalamanTerkunci(): ItemNav | null {
  const { pathname } = useLocation()
  const item = [...SEMUA_NAV]
    .sort((a, b) => b.url.length - a.url.length)
    .find((n) => n.url !== "/" && ruteAktif(n.url, pathname))
  return item && !bolehDilihat(item, sesi()) ? item : null
}

function HalamanTerkunci({ item }: { item: ItemNav }) {
  return (
    <div className="px-4 py-4 md:py-6 lg:px-6">
      <Empty className="border py-16">
        <EmptyHeader>
          <EmptyMedia variant="icon">
            <IconLock />
          </EmptyMedia>
          <EmptyTitle>{item.khusus ? "Khusus Administrator" : "Modul ini belum ditugaskan kepada Anda"}</EmptyTitle>
          <EmptyDescription>
            {item.khusus
              ? "Akun pegawai dikelola oleh Administrator di Sub Bagian Tata Usaha."
              : `${item.judul} hanya untuk pegawai yang ditugaskan mengelolanya. Minta Administrator di Sub Bagian Tata Usaha menambahkan modulnya ke akun Anda.`}
          </EmptyDescription>
        </EmptyHeader>
        <EmptyContent>
          <Button asChild variant="outline">
            <Link to="/">Kembali ke Ringkasan</Link>
          </Button>
        </EmptyContent>
      </Empty>
    </div>
  )
}

/**
 * Kembali ke halaman login bila sesi di server sudah berakhir (keluar di tab lain,
 * akun dinonaktifkan, atau lama tidak aktif). Diperiksa saat tab kembali dilihat,
 * bukan berkala, supaya tab yang terbuka tanpa dipakai tidak memperpanjang sesi.
 */
function useJagaSesi() {
  React.useEffect(() => {
    const periksa = () => {
      if (document.visibilityState !== "visible") return
      muatSesi()
        .then((sesi) => {
          if (!sesi) window.location.replace(urlLogin("berakhir"))
        })
        .catch(() => {
          // koneksi terputus: periksa lagi saat tab dilihat berikutnya
        })
    }
    document.addEventListener("visibilitychange", periksa)
    return () => document.removeEventListener("visibilitychange", periksa)
  }, [])
}

export function Layout() {
  useJagaSesi()
  const { data: situs } = usePengaturan("situs")
  const terkunci = useHalamanTerkunci()

  React.useEffect(() => {
    aturBasisPortal(situs?.urlPortal)
  }, [situs?.urlPortal])

  return (
    <CommandMenuProvider>
      <KonfirmasiProvider>
        <SidebarProvider
          style={
            {
              "--sidebar-width": "calc(var(--spacing) * 72)",
              "--header-height": "calc(var(--spacing) * 12)",
            } as React.CSSProperties
          }
        >
          <AppSidebar variant="inset" />
          <SidebarInset>
            <SiteHeader />
            {api.sementara && (
              <div className="px-4 pt-4 lg:px-6">
                <Alert variant="destructive">
                  <IconAlertTriangle />
                  <AlertTitle>Penyimpanan peramban tidak tersedia</AlertTitle>
                  <AlertDescription>
                    Perubahan hanya tersimpan sementara dan hilang saat tab ditutup. Matikan mode
                    privat/penyamaran atau izinkan penyimpanan situs untuk menyimpan data.
                  </AlertDescription>
                </Alert>
              </div>
            )}
            <div className="flex flex-1 flex-col">
              <div className="@container/main flex flex-1 flex-col gap-2">
                <React.Suspense
                  fallback={
                    <div className="p-4 lg:p-6">
                      <KerangkaMemuat />
                    </div>
                  }
                >
                  {terkunci ? <HalamanTerkunci item={terkunci} /> : <Outlet />}
                </React.Suspense>
              </div>
            </div>
          </SidebarInset>
        </SidebarProvider>
        <ScrollRestoration />
      </KonfirmasiProvider>
    </CommandMenuProvider>
  )
}
