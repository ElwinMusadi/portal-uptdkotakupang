import {
  IconDeviceDesktop,
  IconDotsVertical,
  IconExternalLink,
  IconLogout,
  IconMoon,
  IconPalette,
  IconSun,
  IconUserCircle,
} from "@tabler/icons-react"
import { useNavigate } from "react-router"

import { useTheme, type Theme } from "@/components/theme-provider"
import { useTutupSeluler } from "@/components/nav-main"
import { Avatar, AvatarFallback } from "@/components/ui/avatar"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuRadioGroup,
  DropdownMenuRadioItem,
  DropdownMenuSeparator,
  DropdownMenuSub,
  DropdownMenuSubContent,
  DropdownMenuSubTrigger,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import {
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  useSidebar,
} from "@/components/ui/sidebar"
import { bacaSesi, keluar } from "@/lib/auth"
import { inisial } from "@/lib/format"
import { usePengaturan } from "@/lib/queries"
import { urlPortal } from "@/lib/portal"

export function NavUser() {
  const { isMobile } = useSidebar()
  const navigate = useNavigate()
  const tutup = useTutupSeluler()
  const { theme, setTheme } = useTheme()
  const { data: akun } = usePengaturan("akun")
  const nip = bacaSesi()?.nip ?? ""
  const nama = akun?.nama ?? "Pengelola PINTU"

  const identitas = (
    <>
      <Avatar className="h-8 w-8 rounded-lg">
        <AvatarFallback className="rounded-lg bg-primary text-xs font-semibold text-primary-foreground">
          {inisial(nama)}
        </AvatarFallback>
      </Avatar>
      <div className="grid flex-1 text-left text-sm leading-tight">
        <span className="truncate font-medium">{nama}</span>
        <span className="truncate text-xs text-muted-foreground tabular-nums">NIP {nip}</span>
      </div>
    </>
  )

  return (
    <SidebarMenu>
      <SidebarMenuItem>
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <SidebarMenuButton
              size="lg"
              className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
            >
              {identitas}
              <IconDotsVertical className="ml-auto size-4" />
            </SidebarMenuButton>
          </DropdownMenuTrigger>
          <DropdownMenuContent
            className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
            side={isMobile ? "bottom" : "right"}
            align="end"
            sideOffset={4}
          >
            <DropdownMenuLabel className="p-0 font-normal">
              <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                {identitas}
              </div>
              {akun?.peran && (
                <p className="px-1 pb-1.5 text-xs text-muted-foreground">
                  {akun.peran} · {akun.unit}
                </p>
              )}
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
              <DropdownMenuItem
                onSelect={() => {
                  tutup()
                  navigate("/pengaturan?tab=akun")
                }}
              >
                <IconUserCircle />
                Akun saya
              </DropdownMenuItem>
              <DropdownMenuSub>
                <DropdownMenuSubTrigger>
                  <IconPalette />
                  Tema
                </DropdownMenuSubTrigger>
                <DropdownMenuSubContent>
                  <DropdownMenuRadioGroup
                    value={theme}
                    onValueChange={(v) => setTheme(v as Theme)}
                  >
                    <DropdownMenuRadioItem value="light">
                      <IconSun />
                      Terang
                    </DropdownMenuRadioItem>
                    <DropdownMenuRadioItem value="dark">
                      <IconMoon />
                      Gelap
                    </DropdownMenuRadioItem>
                    <DropdownMenuRadioItem value="system">
                      <IconDeviceDesktop />
                      Ikuti sistem
                    </DropdownMenuRadioItem>
                  </DropdownMenuRadioGroup>
                </DropdownMenuSubContent>
              </DropdownMenuSub>
              <DropdownMenuItem asChild>
                <a href={urlPortal("index.html")} target="_blank" rel="noopener">
                  <IconExternalLink />
                  Lihat portal
                </a>
              </DropdownMenuItem>
            </DropdownMenuGroup>
            <DropdownMenuSeparator />
            <DropdownMenuItem onSelect={keluar}>
              <IconLogout />
              Keluar
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </SidebarMenuItem>
    </SidebarMenu>
  )
}
