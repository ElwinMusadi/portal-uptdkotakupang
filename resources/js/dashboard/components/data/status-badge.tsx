import type * as React from "react"
import {
  IconArchive,
  IconArrowBackUp,
  IconCircleCheckFilled,
  IconCircleDashed,
  IconEye,
  IconLoader,
  type Icon,
} from "@tabler/icons-react"

import { Badge } from "@/components/ui/badge"
import type { StatusAlur } from "@/lib/api"
import { STATUS_ALUR, type Nada } from "@/lib/meta"
import { cn } from "@/lib/utils"

const WARNA_IKON: Record<Nada, string> = {
  netral: "text-muted-foreground",
  info: "text-brand",
  proses: "text-warning",
  sukses: "text-success",
  peringatan: "text-warning",
  bahaya: "text-destructive",
}

export const IKON_STATUS: Record<StatusAlur, Icon> = {
  draf: IconCircleDashed,
  diperiksa: IconEye,
  persetujuan: IconLoader,
  terbit: IconCircleCheckFilled,
  dikembalikan: IconArrowBackUp,
  arsip: IconArchive,
}

/** Badge status: selalu ikon + label, tidak hanya warna */
export function NadaBadge({
  nada,
  ikon: Ikon,
  children,
  className,
}: {
  nada: Nada
  ikon?: Icon
  children: React.ReactNode
  className?: string
}) {
  return (
    <Badge variant="outline" className={cn("gap-1 px-1.5 text-muted-foreground", className)}>
      {Ikon && <Ikon className={cn("size-3.5", WARNA_IKON[nada])} aria-hidden="true" />}
      <span className="text-foreground/80">{children}</span>
    </Badge>
  )
}

export function StatusAlurBadge({ status, className }: { status: StatusAlur; className?: string }) {
  const meta = STATUS_ALUR[status]
  return (
    <NadaBadge nada={meta.nada} ikon={IKON_STATUS[status]} className={className}>
      {meta.label}
    </NadaBadge>
  )
}

/** Titik status kecil + label (untuk loket, pesan, pemutakhiran) */
export function TitikStatus({ nada, children }: { nada: Nada; children: React.ReactNode }) {
  const warna: Record<Nada, string> = {
    netral: "bg-muted-foreground/60",
    info: "bg-brand",
    proses: "bg-warning",
    sukses: "bg-success",
    peringatan: "bg-warning",
    bahaya: "bg-destructive",
  }
  return (
    <span className="inline-flex items-center gap-1.5 text-sm">
      <span aria-hidden="true" className={cn("size-2 shrink-0 rounded-full", warna[nada])} />
      {children}
    </span>
  )
}
