import * as React from "react"
import { IconAlertTriangle } from "@tabler/icons-react"
import { Outlet, ScrollRestoration } from "react-router"

import { AppSidebar } from "@/components/app-sidebar"
import { CommandMenuProvider } from "@/components/command-menu"
import { KonfirmasiProvider } from "@/components/data/confirm"
import { KerangkaMemuat } from "@/components/form/settings-form"
import { SiteHeader } from "@/components/site-header"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { SidebarInset, SidebarProvider } from "@/components/ui/sidebar"
import { api } from "@/lib/api"
import { bacaSesi, urlLogin } from "@/lib/auth"
import { aturBasisPortal } from "@/lib/portal"
import { usePengaturan } from "@/lib/queries"

/** Arahkan ke halaman login bila sesi berakhir atau pengguna keluar di tab lain */
function useJagaSesi() {
  React.useEffect(() => {
    const periksa = () => {
      if (!bacaSesi()) window.location.replace(urlLogin("berakhir"))
    }
    const id = window.setInterval(periksa, 60_000)
    document.addEventListener("visibilitychange", periksa)
    window.addEventListener("storage", periksa)
    return () => {
      window.clearInterval(id)
      document.removeEventListener("visibilitychange", periksa)
      window.removeEventListener("storage", periksa)
    }
  }, [])
}

export function Layout() {
  useJagaSesi()
  const { data: situs } = usePengaturan("situs")

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
                  <Outlet />
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
