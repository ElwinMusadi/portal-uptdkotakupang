import { IconCirclePlusFilled, IconMail } from "@tabler/icons-react"
import { Link, useLocation, useNavigate } from "react-router"

import { BUAT_CEPAT, type ItemNav } from "@/app/nav"
import { Button } from "@/components/ui/button"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import {
  SidebarGroup,
  SidebarGroupContent,
  SidebarMenu,
  SidebarMenuBadge,
  SidebarMenuButton,
  SidebarMenuItem,
  useSidebar,
} from "@/components/ui/sidebar"
import { useKoleksi } from "@/lib/queries"

export function ruteAktif(url: string, pathname: string) {
  const bersih = url.split("?")[0]
  return bersih === "/" ? pathname === "/" : pathname === bersih || pathname.startsWith(bersih + "/")
}

/** Tutup sidebar seluler setelah pengguna memilih menu */
export function useTutupSeluler() {
  const { isMobile, setOpenMobile } = useSidebar()
  return () => {
    if (isMobile) setOpenMobile(false)
  }
}

export function NavMain({ items }: { items: ItemNav[] }) {
  const { pathname } = useLocation()
  const navigate = useNavigate()
  const tutup = useTutupSeluler()
  const { isMobile } = useSidebar()
  const { data: pesan } = useKoleksi("pesan")
  const baru = pesan?.filter((p) => p.status === "baru").length ?? 0

  return (
    <SidebarGroup>
      <SidebarGroupContent className="flex flex-col gap-2">
        <SidebarMenu>
          <SidebarMenuItem className="flex items-center gap-2">
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <SidebarMenuButton
                  tooltip="Buat konten"
                  className="min-w-8 bg-primary text-primary-foreground duration-200 ease-linear hover:bg-primary/90 hover:text-primary-foreground active:bg-primary/90 active:text-primary-foreground data-[state=open]:bg-primary/90 data-[state=open]:text-primary-foreground"
                >
                  <IconCirclePlusFilled />
                  <span>Buat konten</span>
                </SidebarMenuButton>
              </DropdownMenuTrigger>
              <DropdownMenuContent
                className="w-60 rounded-lg"
                side={isMobile ? "bottom" : "right"}
                align="start"
                sideOffset={4}
              >
                <DropdownMenuLabel className="text-xs text-muted-foreground">
                  Buat baru
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                {BUAT_CEPAT.map((b) => (
                  <DropdownMenuItem
                    key={b.url}
                    onSelect={() => {
                      tutup()
                      navigate(b.url)
                    }}
                  >
                    <b.ikon />
                    {b.judul}
                  </DropdownMenuItem>
                ))}
              </DropdownMenuContent>
            </DropdownMenu>
            <Button
              asChild
              size="icon"
              className="relative size-8 group-data-[collapsible=icon]:opacity-0"
              variant="outline"
            >
              <Link to="/pesan?status=baru" onClick={tutup}>
                <IconMail />
                <span className="sr-only">
                  Pesan masuk{baru ? `, ${baru} belum dibaca` : ""}
                </span>
                {baru > 0 && (
                  <span
                    aria-hidden="true"
                    className="absolute -top-1.5 -right-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-brand px-1 text-[10px] font-semibold text-white tabular-nums dark:text-[#0f1319]"
                  >
                    {baru > 99 ? "99+" : baru}
                  </span>
                )}
              </Link>
            </Button>
          </SidebarMenuItem>
        </SidebarMenu>
        <SidebarMenu>
          {items.map((item) => (
            <SidebarMenuItem key={item.url}>
              <SidebarMenuButton
                asChild
                tooltip={item.judul}
                isActive={ruteAktif(item.url, pathname)}
              >
                <Link to={item.url} onClick={tutup}>
                  <item.ikon />
                  <span>{item.judul}</span>
                </Link>
              </SidebarMenuButton>
              {item.url === "/pesan" && baru > 0 && (
                <SidebarMenuBadge className="tabular-nums">{baru}</SidebarMenuBadge>
              )}
            </SidebarMenuItem>
          ))}
        </SidebarMenu>
      </SidebarGroupContent>
    </SidebarGroup>
  )
}
