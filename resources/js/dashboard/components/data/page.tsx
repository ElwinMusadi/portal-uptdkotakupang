import type * as React from "react"

import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { cn } from "@/lib/utils"

/** Badan halaman: jarak tepi mengikuti template dashboard-01 */
export function Halaman({ children, className }: { children: React.ReactNode; className?: string }) {
  return (
    <div className={cn("flex flex-col gap-4 px-4 py-4 md:gap-6 md:py-6 lg:px-6", className)}>
      {children}
    </div>
  )
}

/** Pengantar halaman: deskripsi singkat + tombol utama */
export function PengantarHalaman({
  deskripsi,
  children,
}: {
  deskripsi: React.ReactNode
  children?: React.ReactNode
}) {
  return (
    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <p className="max-w-2xl text-sm text-muted-foreground">{deskripsi}</p>
      {children && <div className="flex shrink-0 flex-wrap items-center gap-2">{children}</div>}
    </div>
  )
}

/** Kartu bagian formulir */
export function Bagian({
  judul,
  deskripsi,
  aksi,
  children,
  className,
  id,
}: {
  judul: React.ReactNode
  deskripsi?: React.ReactNode
  aksi?: React.ReactNode
  children: React.ReactNode
  className?: string
  id?: string
}) {
  return (
    <Card id={id} className={cn("scroll-mt-20 gap-5", className)}>
      <CardHeader>
        <CardTitle className="text-base">{judul}</CardTitle>
        {deskripsi && <CardDescription>{deskripsi}</CardDescription>}
        {aksi && <CardAction>{aksi}</CardAction>}
      </CardHeader>
      <CardContent className="grid gap-5">{children}</CardContent>
    </Card>
  )
}

export function Baris({ children, className }: { children: React.ReactNode; className?: string }) {
  return <div className={cn("grid gap-5 sm:grid-cols-2", className)}>{children}</div>
}
