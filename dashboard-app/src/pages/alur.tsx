import * as React from "react"
import { IconDotsVertical, IconSearch } from "@tabler/icons-react"
import { Link } from "react-router"

import { MenuAksiAlur, useAksiAlur } from "@/components/alur/alur"
import { Halaman, PengantarHalaman } from "@/components/data/page"
import { IKON_STATUS, StatusAlurBadge } from "@/components/data/status-badge"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { Skeleton } from "@/components/ui/skeleton"
import type { StatusAlur } from "@/lib/api"
import { tanggalJam, waktuRelatif } from "@/lib/format"
import { JENIS_KOLEKSI, sejakStatus, useItemAlur, type ItemAlur } from "@/lib/hitung"
import { STATUS_ALUR, TAHAP_ALUR } from "@/lib/meta"
import { cn } from "@/lib/utils"

const BATAS_TERBIT = 8

function KartuAlur({ item }: { item: ItemAlur }) {
  const { jalankan, dialog } = useAksiAlur(item.koleksi)
  const sejak = sejakStatus(item)
  return (
    <li className="grid gap-2 rounded-lg border bg-card p-3 text-sm shadow-xs">
      <div className="flex items-start gap-2">
        <Badge variant="outline" className="px-1.5 text-muted-foreground">
          {item.jenis}
        </Badge>
        {item.status === "dikembalikan" && <StatusAlurBadge status="dikembalikan" />}
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" size="icon" className="-mt-1 -mr-1 ml-auto size-7 text-muted-foreground">
              <IconDotsVertical className="size-4" />
              <span className="sr-only">Tindakan untuk {item.judul}</span>
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" className="w-56">
            <DropdownMenuItem asChild>
              <Link to={item.url}>Buka penyunting</Link>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <MenuAksiAlur item={item} jalankan={(i, a) => void jalankan(i, a)} />
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
      <Link to={item.url} className="font-medium underline-offset-4 hover:underline">
        {item.judul}
      </Link>
      {item.status === "dikembalikan" && item.catatanRevisi && (
        <p className="rounded-md bg-warning-soft px-2 py-1 text-xs text-warning">“{item.catatanRevisi}”</p>
      )}
      <p className="flex flex-wrap justify-between gap-x-3 gap-y-1 text-xs text-muted-foreground">
        <span>{item.penyedia}</span>
        <time dateTime={sejak} title={tanggalJam(sejak)}>
          {waktuRelatif(sejak)}
        </time>
      </p>
      {dialog}
    </li>
  )
}

export default function HalamanAlur() {
  const { data } = useItemAlur()
  const [kueri, setKueri] = React.useState("")
  const [jenis, setJenis] = React.useState("semua")
  const [arsip, setArsip] = React.useState(false)

  const tersaring = React.useMemo(() => {
    const q = kueri.trim().toLowerCase()
    return data?.filter(
      (x) =>
        (jenis === "semua" || x.koleksi === jenis) &&
        (!q || `${x.judul} ${x.penyedia}`.toLowerCase().includes(q))
    )
  }, [data, kueri, jenis])

  const kolom = (status: StatusAlur) => {
    const isi = tersaring?.filter((x) =>
      status === "draf" ? x.status === "draf" || x.status === "dikembalikan" : x.status === status
    )
    return (isi ?? []).sort((a, b) => sejakStatus(b).localeCompare(sejakStatus(a)))
  }

  return (
    <Halaman>
      <PengantarHalaman deskripsi="Setiap informasi melewati empat peran sebelum terbit: penyedia data, pemeriksa, atasan langsung sebagai penyetuju, dan pengelola publikasi. Geser konten antar-tahap lewat menu ⋮ pada kartu." />
      <div className="flex flex-wrap items-center gap-2">
        <div className="relative w-full sm:w-64">
          <Label htmlFor="cari-alur" className="sr-only">
            Cari konten
          </Label>
          <IconSearch className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            id="cari-alur"
            type="search"
            value={kueri}
            onChange={(e) => setKueri(e.target.value)}
            placeholder="Cari judul atau unit…"
            className="h-8 pl-8"
          />
        </div>
        <Select value={jenis} onValueChange={setJenis}>
          <SelectTrigger size="sm" className="w-fit min-w-40" aria-label="Jenis konten">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="semua">Semua jenis</SelectItem>
            {Object.entries(JENIS_KOLEKSI).map(([nilai, label]) => (
              <SelectItem key={nilai} value={nilai}>
                {label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
        <Button variant="ghost" size="sm" className="ml-auto" onClick={() => setArsip((a) => !a)}>
          {arsip ? "Sembunyikan arsip" : `Lihat arsip (${tersaring?.filter((x) => x.status === "arsip").length ?? 0})`}
        </Button>
      </div>

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        {TAHAP_ALUR.map((status, i) => {
          const isi = kolom(status)
          const tampil = status === "terbit" ? isi.slice(0, BATAS_TERBIT) : isi
          const Ikon = IKON_STATUS[status]
          return (
            <section
              key={status}
              aria-labelledby={`kolom-${status}`}
              className="flex flex-col gap-3 rounded-xl border bg-muted/30 p-3"
            >
              <header className="grid gap-0.5 px-1">
                <h2 id={`kolom-${status}`} className="flex items-center gap-2 text-sm font-semibold">
                  <Ikon className={cn("size-4", status === "terbit" ? "text-success" : "text-muted-foreground")} aria-hidden="true" />
                  {String(i + 1).padStart(2, "0")} · {STATUS_ALUR[status].label}
                  <Badge variant="secondary" className="ml-auto tabular-nums">
                    {isi.length}
                  </Badge>
                </h2>
                <p className="text-xs text-muted-foreground">{STATUS_ALUR[status].peran}</p>
              </header>
              {!data ? (
                <Skeleton className="h-28 w-full" />
              ) : tampil.length === 0 ? (
                <p className="rounded-lg border border-dashed p-4 text-center text-xs text-muted-foreground">Kosong</p>
              ) : (
                <ol className="grid gap-2">
                  {tampil.map((item) => (
                    <KartuAlur key={`${item.koleksi}-${item.id}`} item={item} />
                  ))}
                </ol>
              )}
              {status === "terbit" && isi.length > BATAS_TERBIT && (
                <p className="px-1 text-xs text-muted-foreground">+{isi.length - BATAS_TERBIT} konten terbit lainnya</p>
              )}
            </section>
          )
        })}
      </div>

      {arsip && (
        <section aria-labelledby="kolom-arsip" className="grid gap-3 rounded-xl border bg-muted/30 p-3">
          <h2 id="kolom-arsip" className="px-1 text-sm font-semibold">
            Diarsipkan · tidak tayang di portal
          </h2>
          <ol className="grid gap-2 md:grid-cols-2 xl:grid-cols-4">
            {tersaring
              ?.filter((x) => x.status === "arsip")
              .map((item) => <KartuAlur key={`${item.koleksi}-${item.id}`} item={item} />)}
          </ol>
        </section>
      )}
    </Halaman>
  )
}
