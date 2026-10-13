import { IconDots, IconExternalLink, IconPlus } from "@tabler/icons-react"
import { Link, useLocation, useNavigate } from "react-router"

import type { ItemNav } from "@/app/nav"
import { ruteAktif, useTutupSeluler } from "@/components/nav-main"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import {
  SidebarGroup,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuAction,
  SidebarMenuButton,
  SidebarMenuItem,
  useSidebar,
} from "@/components/ui/sidebar"
import { urlPortal } from "@/lib/portal"

export function NavDocuments({ label, items }: { label: string; items: ItemNav[] }) {
  const { isMobile } = useSidebar()
  const { pathname } = useLocation()
  const navigate = useNavigate()
  const tutup = useTutupSeluler()

  return (
    <SidebarGroup className="group-data-[collapsible=icon]:hidden">
      <SidebarGroupLabel>{label}</SidebarGroupLabel>
      <SidebarMenu>
        {items.map((item) => (
          <SidebarMenuItem key={item.url}>
            <SidebarMenuButton asChild isActive={ruteAktif(item.url, pathname)}>
              <Link to={item.url} onClick={tutup}>
                <item.ikon />
                <span>{item.judul}</span>
              </Link>
            </SidebarMenuButton>
            {(item.portal || item.tambah) && (
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <SidebarMenuAction showOnHover className="rounded-sm data-[state=open]:bg-accent">
                    <IconDots />
                    <span className="sr-only">Pilihan untuk {item.judul}</span>
                  </SidebarMenuAction>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                  className="w-48 rounded-lg"
                  side={isMobile ? "bottom" : "right"}
                  align={isMobile ? "end" : "start"}
                >
                  {item.tambah && (
                    <DropdownMenuItem
                      onSelect={() => {
                        tutup()
                        navigate(item.tambah!)
                      }}
                    >
                      <IconPlus />
                      <span>{item.labelTambah ?? "Tambah baru"}</span>
                    </DropdownMenuItem>
                  )}
                  {item.portal && (
                    <DropdownMenuItem asChild>
                      <a href={urlPortal(item.portal)} target="_blank" rel="noopener">
                        <IconExternalLink />
                        <span>Lihat di portal</span>
                      </a>
                    </DropdownMenuItem>
                  )}
                </DropdownMenuContent>
              </DropdownMenu>
            )}
          </SidebarMenuItem>
        ))}
      </SidebarMenu>
    </SidebarGroup>
  )
}
