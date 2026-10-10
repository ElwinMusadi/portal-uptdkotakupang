import * as React from "react"
import { Link } from "react-router"

import { NAV_GRUP, NAV_SEKUNDER, NAV_UTAMA } from "@/app/nav"
import { NavDocuments } from "@/components/nav-documents"
import { NavMain } from "@/components/nav-main"
import { NavSecondary } from "@/components/nav-secondary"
import { NavUser } from "@/components/nav-user"
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from "@/components/ui/sidebar"
import { usePengaturan } from "@/lib/queries"

export function AppSidebar({ ...props }: React.ComponentProps<typeof Sidebar>) {
  const { data: situs } = usePengaturan("situs")

  return (
    <Sidebar collapsible="offcanvas" role="navigation" aria-label="Menu dashboard" {...props}>
      <SidebarHeader>
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton asChild size="lg" className="data-[slot=sidebar-menu-button]:p-1.5!">
              <Link to="/">
                <img
                  src="./brand/lambang-ntt-96.webp"
                  alt=""
                  width={28}
                  height={30}
                  className="h-[30px] w-auto shrink-0"
                />
                <span className="grid leading-tight">
                  <span className="text-base font-semibold tracking-tight">
                    {situs?.namaPortal ?? "PINTU"}
                  </span>
                  <span className="truncate text-xs text-muted-foreground">
                    {situs?.namaSingkat ?? "Samsat Kota Kupang"}
                  </span>
                </span>
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarHeader>
      <SidebarContent>
        <NavMain items={NAV_UTAMA} />
        {NAV_GRUP.map((grup) => (
          <NavDocuments key={grup.label} label={grup.label} items={grup.item} />
        ))}
        <NavSecondary items={NAV_SEKUNDER} className="mt-auto" />
      </SidebarContent>
      <SidebarFooter>
        <NavUser />
      </SidebarFooter>
    </Sidebar>
  )
}
