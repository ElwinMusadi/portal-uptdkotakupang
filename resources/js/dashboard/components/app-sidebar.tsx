import * as React from "react"
import { Link } from "react-router"

import { NAV_GRUP, NAV_SEKUNDER, NAV_UTAMA, navUntuk } from "@/app/nav"
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
import { sesi } from "@/lib/auth"
import { usePengaturan } from "@/lib/queries"

export function AppSidebar({ ...props }: React.ComponentProps<typeof Sidebar>) {
  const { data: situs } = usePengaturan("situs")

  return (
    <Sidebar collapsible="offcanvas" role="navigation" aria-label="Menu dashboard" {...props}>
      <SidebarHeader>
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton asChild size="lg" className="data-[slot=sidebar-menu-button]:p-1.5!">
              <Link to="/" aria-label={`${situs?.namaPortal ?? "PINTU"} — beranda dashboard`}>
                <img
                  src="/assets/img/logo-pintu-nav.png"
                  alt=""
                  width={1107}
                  height={361}
                  className="h-7 w-auto shrink-0 dark:hidden"
                />
                <img
                  src="/assets/img/logo-pintu-nav-putih.png"
                  alt=""
                  width={1107}
                  height={361}
                  className="hidden h-7 w-auto shrink-0 dark:block"
                />
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarHeader>
      <SidebarContent>
        <NavMain items={navUntuk(NAV_UTAMA, sesi())} />
        {NAV_GRUP.map((grup) => ({ ...grup, item: navUntuk(grup.item, sesi()) }))
          .filter((grup) => grup.item.length > 0)
          .map((grup) => (
            <NavDocuments key={grup.label} label={grup.label} items={grup.item} />
          ))}
        <NavSecondary items={navUntuk(NAV_SEKUNDER, sesi())} className="mt-auto" />
      </SidebarContent>
      <SidebarFooter>
        <NavUser />
      </SidebarFooter>
    </Sidebar>
  )
}
