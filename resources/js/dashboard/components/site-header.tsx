import { IconExternalLink, IconMoon, IconSearch, IconSun } from "@tabler/icons-react"
import { useLocation, useMatches } from "react-router"

import { SEMUA_NAV } from "@/app/nav"
import { useCommandMenu } from "@/components/command-menu"
import { ruteAktif } from "@/components/nav-main"
import { useTheme } from "@/components/theme-provider"
import { Button } from "@/components/ui/button"
import { Kbd } from "@/components/ui/kbd"
import { Separator } from "@/components/ui/separator"
import { SidebarTrigger } from "@/components/ui/sidebar"
import { TOMBOL_MOD } from "@/lib/platform"
import { urlPortal } from "@/lib/portal"

export interface HandleRute {
  judul?: string
}

export function SiteHeader() {
  const matches = useMatches()
  const { pathname } = useLocation()
  const { setOpen } = useCommandMenu()
  const { resolvedTheme, setTheme } = useTheme()

  const judul =
    [...matches].reverse().map((m) => (m.handle as HandleRute | undefined)?.judul).find(Boolean) ??
    "Dashboard"
  const modul = [...SEMUA_NAV]
    .sort((a, b) => b.url.length - a.url.length)
    .find((n) => n.url !== "/" && ruteAktif(n.url, pathname))
  const portal = modul?.portal ?? (pathname === "/" ? "index.html" : undefined)

  return (
    <header className="flex h-(--header-height) shrink-0 items-center gap-2 border-b transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-(--header-height)">
      <div className="flex w-full items-center gap-1 px-4 lg:gap-2 lg:px-6">
        <SidebarTrigger className="-ml-1" />
        <Separator orientation="vertical" className="mx-2 data-[orientation=vertical]:h-4" />
        <h1 className="truncate text-base font-medium">{judul}</h1>
        <div className="ml-auto flex items-center gap-1 sm:gap-2">
          <Button
            variant="outline"
            size="sm"
            className="hidden w-44 justify-start text-muted-foreground md:flex lg:w-56"
            onClick={() => setOpen(true)}
          >
            <IconSearch />
            <span className="flex-1 text-left">Cari…</span>
            <Kbd>{TOMBOL_MOD} K</Kbd>
          </Button>
          <Button
            variant="ghost"
            size="icon"
            className="size-8 md:hidden"
            onClick={() => setOpen(true)}
          >
            <IconSearch />
            <span className="sr-only">Cari</span>
          </Button>
          <Button
            variant="ghost"
            size="icon"
            className="size-8"
            onClick={() => setTheme(resolvedTheme === "dark" ? "light" : "dark")}
          >
            {resolvedTheme === "dark" ? <IconSun /> : <IconMoon />}
            <span className="sr-only">
              {resolvedTheme === "dark" ? "Gunakan tema terang" : "Gunakan tema gelap"}
            </span>
          </Button>
          {portal && (
            <Button variant="ghost" asChild size="sm" className="hidden sm:flex">
              <a href={urlPortal(portal)} target="_blank" rel="noopener" className="dark:text-foreground">
                <IconExternalLink />
                Lihat di portal
              </a>
            </Button>
          )}
        </div>
      </div>
    </header>
  )
}
