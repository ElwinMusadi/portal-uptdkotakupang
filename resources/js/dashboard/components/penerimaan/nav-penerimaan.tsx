import { IconNumbers, IconReportMoney, IconTargetArrow } from "@tabler/icons-react"
import { NavLink } from "react-router"

import { cn } from "@/lib/utils"

/** Pindah antarhalaman Laporan Penerimaan: laporan per loket (D-047), realisasi target Form I (D-068), dan nomoratur SKPD (D-071) */
export function NavPenerimaan() {
  const tautan = [
    { ke: "/penerimaan", label: "Laporan", ikon: IconReportMoney },
    { ke: "/penerimaan/realisasi", label: "Realisasi Target", ikon: IconTargetArrow },
    { ke: "/penerimaan/nomoratur", label: "Nomoratur", ikon: IconNumbers },
  ]

  return (
    <nav aria-label="Halaman Laporan Penerimaan" className="-mx-1 overflow-x-auto">
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
