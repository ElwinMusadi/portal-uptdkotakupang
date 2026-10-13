import * as React from "react"
import { IconExternalLink, IconSearch } from "@tabler/icons-react"
import { Link, useLocation } from "react-router"

import type { ItemNav } from "@/app/nav"
import { useCommandMenu } from "@/components/command-menu"
import { ruteAktif, useTutupSeluler } from "@/components/nav-main"
import { Kbd } from "@/components/ui/kbd"
import {
  SidebarGroup,
  SidebarGroupContent,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from "@/components/ui/sidebar"
import { TOMBOL_MOD } from "@/lib/platform"
import { urlPortal } from "@/lib/portal"

export function NavSecondary({
  items,
  ...props
}: {
  items: ItemNav[]
} & React.ComponentPropsWithoutRef<typeof SidebarGroup>) {
  const { pathname } = useLocation()
  const tutup = useTutupSeluler()
  const { setOpen } = useCommandMenu()

  return (
    <SidebarGroup {...props}>
      <SidebarGroupContent>
        <SidebarMenu>
          {items.map((item) => (
            <SidebarMenuItem key={item.url}>
              <SidebarMenuButton asChild isActive={ruteAktif(item.url, pathname)}>
                <Link to={item.url} onClick={tutup}>
                  <item.ikon />
                  <span>{item.judul}</span>
                </Link>
              </SidebarMenuButton>
            </SidebarMenuItem>
          ))}
          <SidebarMenuItem>
            <SidebarMenuButton
              onClick={() => {
                tutup()
                setOpen(true)
              }}
            >
              <IconSearch />
              <span>Cari</span>
              <Kbd className="ml-auto">{TOMBOL_MOD} K</Kbd>
            </SidebarMenuButton>
          </SidebarMenuItem>
          <SidebarMenuItem>
            <SidebarMenuButton asChild>
              <a href={urlPortal("index.html")} target="_blank" rel="noopener">
                <IconExternalLink />
                <span>Lihat portal</span>
              </a>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarGroupContent>
    </SidebarGroup>
  )
}
