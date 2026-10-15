import { IconArchive, IconChartBar, IconDeviceDesktop, IconListNumbers, IconScan, IconSettings } from "@tabler/icons-react"
import { NavLink } from "react-router"

import { sesi } from "@/lib/auth"
import { cn } from "@/lib/utils"

/** Pindah antarhalaman Antrean Online (D-051): Meja Informasi, Loket, Pos Proses, Arsip, Ringkasan, dan Pengaturan (Administrator) */
export function NavAntrean() {
  const administrator = sesi().peran === "administrator"
  const tautan = [
    { ke: "/antrean", label: "Meja Informasi", ikon: IconListNumbers },
    { ke: "/antrean/loket", label: "Loket", ikon: IconDeviceDesktop },
    { ke: "/antrean/pos", label: "Pos Proses", ikon: IconScan },
    { ke: "/antrean/arsip", label: "Arsip", ikon: IconArchive },
    { ke: "/antrean/ringkasan", label: "Ringkasan", ikon: IconChartBar },
    ...(administrator ? [{ ke: "/antrean/pengaturan", label: "Pengaturan", ikon: IconSettings }] : []),
  ]

  return (
    <nav aria-label="Halaman Antrean Online" className="-mx-1 overflow-x-auto">
      <ul className="inline-flex gap-1 rounded-lg bg-muted p-1" role="list">
        {tautan.map(({ ke, label, ikon: Ikon }) => (
          <li key={ke}>
            <NavLink
              to={ke}
              end
              className={({ isActive }) =>
                cn(
                  "inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium whitespace-nowrap text-muted-foreground transition-colors hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none",
                  isActive && "bg-background text-foreground shadow-sm"
                )
              }
            >
              <Ikon className="size-4" aria-hidden="true" />
              {label}
            </NavLink>
          </li>
        ))}
      </ul>
    </nav>
  )
}
