import * as React from "react"
import { IconArrowRight, IconDotsVertical, IconExternalLink } from "@tabler/icons-react"
import { Link, useNavigate } from "react-router"

import { MenuAksiAlur, useAksiAlur } from "@/components/alur/alur"
import { DataTable, pembantuKolom } from "@/components/data/data-table"
import { NadaBadge, StatusAlurBadge, TitikStatus } from "@/components/data/status-badge"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { Label } from "@/components/ui/label"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { Skeleton } from "@/components/ui/skeleton"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { tanggalJam, waktuRelatif } from "@/lib/format"
import { useItemAlur, usePemutakhiran, type ItemAlur } from "@/lib/hitung"
import { MODUL_PANTAU, PEMERIKSA, PESAN_JENIS, PESAN_STATUS } from "@/lib/meta"
import { useAktivitas, useKoleksi, useUbahEntri } from "@/lib/queries"

function PilihPemeriksa({ item }: { item: ItemAlur }) {
  const ubah = useUbahEntri(item.koleksi)
  const id = React.useId()
  return (
    <>
      <Label htmlFor={id} className="sr-only">
        Pemeriksa untuk {item.judul}
      </Label>
      <Select
        value={item.pemeriksa || undefined}
        onValueChange={(v) => ubah.mutate({ id: item.id, patch: { pemeriksa: v } as never })}
      >
        <SelectTrigger
          id={id}
          size="sm"
          className="w-52 **:data-[slot=select-value]:block **:data-[slot=select-value]:truncate"
        >
          <SelectValue placeholder="Tugaskan pemeriksa" />
        </SelectTrigger>
        <SelectContent align="end">
          {PEMERIKSA.map((p) => (
            <SelectItem key={p} value={p}>
              {p}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>
    </>
  )
}

function AksiBaris({ item }: { item: ItemAlur }) {
  const navigate = useNavigate()
  const { jalankan, dialog } = useAksiAlur(item.koleksi)
  return (
    <>
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button
            variant="ghost"
            className="flex size-8 text-muted-foreground data-[state=open]:bg-muted"
            size="icon"
          >
            <IconDotsVertical />
            <span className="sr-only">Pilihan untuk {item.judul}</span>
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" className="w-56">
          <DropdownMenuItem onSelect={() => navigate(item.url)}>
            <IconArrowRight />
            Buka penyunting
          </DropdownMenuItem>
          <DropdownMenuSeparator />
          <MenuAksiAlur item={item} jalankan={(i, a) => void jalankan(i, a)} />
        </DropdownMenuContent>
      </DropdownMenu>
      {dialog}
    </>
  )
}

const k = pembantuKolom<ItemAlur>()
const kolomAntrean = [
  k.accessor("judul", {
    header: "Judul",
    cell: ({ row }) => (
      <Button variant="link" asChild className="h-auto max-w-[22rem] min-w-48 justify-start px-0 text-left whitespace-normal text-foreground">
        <Link to={row.original.url}>{row.original.judul}</Link>
      </Button>
    ),
    enableHiding: false,
  }),
  k.accessor("jenis", {
    header: "Jenis",
    cell: ({ row }) => (
      <div className="w-28">
        <Badge variant="outline" className="px-1.5 text-muted-foreground">
          {row.original.jenis}
        </Badge>
      </div>
    ),
  }),
  k.accessor("status", {
    header: "Status",
    cell: ({ row }) => <StatusAlurBadge status={row.original.status} />,
  }),
  k.accessor("penyedia", {
    header: "Penyedia",
    cell: ({ row }) => <span className="text-sm text-muted-foreground">{row.original.penyedia}</span>,
  }),
  k.accessor("pemeriksa", {
    header: "Pemeriksa",
    cell: ({ row }) => <PilihPemeriksa item={row.original} />,
  }),
  k.accessor("diperbarui", {
    header: "Diperbarui",
    cell: ({ row }) => (
      <time
        dateTime={row.original.diperbarui}
        title={tanggalJam(row.original.diperbarui)}
        className="text-sm whitespace-nowrap text-muted-foreground"
      >
        {waktuRelatif(row.original.diperbarui)}
      </time>
    ),
  }),
  k.display({ id: "aksi", cell: ({ row }) => <AksiBaris item={row.original} /> }),
]

export function AntreanKerja() {
  const [tab, setTab] = React.useState("antrean")
  const { data: alur } = useItemAlur()
  const { data: pesan } = useKoleksi("pesan")
  const pantau = usePemutakhiran()
  const { data: aktivitas } = useAktivitas(30)

  const antrean = React.useMemo(
    () => alur?.filter((x) => x.status !== "terbit" && x.status !== "arsip"),
    [alur]
  )
  const pesanTerbaru = React.useMemo(
    () => pesan?.slice().sort((a, b) => b.diterima.localeCompare(a.diterima)).slice(0, 8),
    [pesan]
  )
  const pesanBaru = pesan?.filter((p) => p.status === "baru").length ?? 0
  const jatuhTempo = pantau?.filter((p) => p.status !== "terkini").length ?? 0

  const TABS = [
    { nilai: "antrean", label: "Antrean kerja", jumlah: antrean?.length },
    { nilai: "pesan", label: "Pesan terbaru", jumlah: pesanBaru },
    { nilai: "pemutakhiran", label: "Pemutakhiran", jumlah: jatuhTempo },
    { nilai: "aktivitas", label: "Aktivitas" },
  ]

  return (
    <Tabs value={tab} onValueChange={setTab} className="w-full flex-col justify-start gap-6">
      <div className="flex items-center justify-between px-4 lg:px-6">
        <Label htmlFor="view-selector" className="sr-only">
          Tampilan
        </Label>
        <Select value={tab} onValueChange={setTab}>
          <SelectTrigger className="flex w-fit @4xl/main:hidden" size="sm" id="view-selector">
            <SelectValue placeholder="Pilih tampilan" />
          </SelectTrigger>
          <SelectContent>
            {TABS.map((t) => (
              <SelectItem key={t.nilai} value={t.nilai}>
                {t.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
        <TabsList className="hidden **:data-[slot=badge]:size-5 **:data-[slot=badge]:rounded-full **:data-[slot=badge]:bg-muted-foreground/30 **:data-[slot=badge]:px-1 @4xl/main:flex">
          {TABS.map((t) => (
            <TabsTrigger key={t.nilai} value={t.nilai}>
              {t.label}
              {!!t.jumlah && <Badge variant="secondary">{t.jumlah}</Badge>}
            </TabsTrigger>
          ))}
        </TabsList>
        <Button variant="outline" size="sm" asChild>
          <Link to="/alur">
            <span className="hidden lg:inline">Buka papan alur</span>
            <span className="lg:hidden">Papan alur</span>
            <IconArrowRight />
          </Link>
        </Button>
      </div>

      <TabsContent value="antrean" className="relative flex flex-col gap-4 overflow-auto px-4 lg:px-6">
        <DataTable
          data={antrean}
          columns={kolomAntrean}
          label="Antrean kerja alur publikasi"
          memuat={!antrean}
          pilih={false}
          cari={{ placeholder: "Cari judul…", teks: (x) => `${x.judul} ${x.jenis} ${x.penyedia}` }}
          kosong={{
            judul: "Tidak ada antrean",
            deskripsi: "Semua konten sudah terbit atau diarsipkan.",
          }}
        />
      </TabsContent>

      <TabsContent value="pesan" className="flex flex-col px-4 lg:px-6">
        <div className="overflow-hidden rounded-lg border">
          <Table aria-label="Pesan terbaru">
            <TableHeader className="bg-muted">
              <TableRow>
                <TableHead>Tiket</TableHead>
                <TableHead>Jenis</TableHead>
                <TableHead>Isi pesan</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Diterima</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {!pesanTerbaru
                ? Array.from({ length: 3 }).map((_, i) => (
                    <TableRow key={i}>
                      <TableCell colSpan={5}>
                        <Skeleton className="h-6 w-full" />
                      </TableCell>
                    </TableRow>
                  ))
                : pesanTerbaru.map((p) => (
                    <TableRow key={p.id}>
                      <TableCell className="font-medium tabular-nums">
                        <Link to={`/pesan?ubah=${p.id}`} className="underline-offset-4 hover:underline">
                          {p.tiket}
                        </Link>
                      </TableCell>
                      <TableCell>
                        <Badge variant="outline" className="px-1.5 text-muted-foreground">
                          {PESAN_JENIS[p.jenis]}
                        </Badge>
                      </TableCell>
                      <TableCell className="max-w-[28rem] truncate">{p.isi}</TableCell>
                      <TableCell>
                        <TitikStatus nada={PESAN_STATUS[p.status].nada}>{PESAN_STATUS[p.status].label}</TitikStatus>
                      </TableCell>
                      <TableCell className="whitespace-nowrap text-muted-foreground">
                        {waktuRelatif(p.diterima)}
                      </TableCell>
                    </TableRow>
                  ))}
            </TableBody>
          </Table>
        </div>
        <Button variant="link" asChild className="mt-2 w-fit px-0">
          <Link to="/pesan">
            Lihat semua pesan
            <IconArrowRight />
          </Link>
        </Button>
      </TabsContent>

      <TabsContent value="pemutakhiran" className="flex flex-col px-4 lg:px-6">
        <div className="overflow-hidden rounded-lg border">
          <Table aria-label="Status pemutakhiran konten">
            <TableHeader className="bg-muted">
              <TableRow>
                <TableHead>Konten</TableHead>
                <TableHead>Frekuensi</TableHead>
                <TableHead>Terakhir</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>
                  <span className="sr-only">Tindakan</span>
                </TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {pantau?.map((p) => (
                <TableRow key={p.id}>
                  <TableCell>
                    <div className="font-medium">{p.konten}</div>
                    <div className="text-xs text-muted-foreground">{p.unit}</div>
                  </TableCell>
                  <TableCell className="text-muted-foreground">{p.frekuensi}</TableCell>
                  <TableCell className="whitespace-nowrap text-muted-foreground">
                    {p.terakhir ? waktuRelatif(p.terakhir) : "—"}
                  </TableCell>
                  <TableCell>
                    {p.status === "jatuh-tempo" ? (
                      <NadaBadge nada="peringatan">Jatuh tempo</NadaBadge>
                    ) : p.status === "segera" ? (
                      <TitikStatus nada="proses">Segera ({p.sisa} hari)</TitikStatus>
                    ) : (
                      <TitikStatus nada="sukses">Terkini</TitikStatus>
                    )}
                  </TableCell>
                  <TableCell className="text-right">
                    <Button variant="ghost" size="sm" asChild>
                      <Link to={MODUL_PANTAU[p.modul].rute}>
                        Perbarui
                        <IconExternalLink />
                      </Link>
                    </Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </div>
      </TabsContent>

      <TabsContent value="aktivitas" className="flex flex-col px-4 lg:px-6">
        <ol className="divide-y rounded-lg border">
          {aktivitas?.length === 0 && (
            <li className="p-4 text-sm text-muted-foreground">Belum ada aktivitas.</li>
          )}
          {aktivitas?.map((a) => (
            <li key={a.id} className="flex flex-wrap items-baseline gap-x-2 gap-y-0.5 px-4 py-3 text-sm">
              <span className="font-medium">{a.oleh}</span>
              <span>{a.aksi}</span>
              <span className="font-medium">{a.target}</span>
              <span className="text-muted-foreground">· {a.modul}</span>
              <time
                dateTime={a.waktu}
                title={tanggalJam(a.waktu)}
                className="ml-auto text-xs text-muted-foreground"
              >
                {waktuRelatif(a.waktu)}
              </time>
            </li>
          ))}
        </ol>
      </TabsContent>
    </Tabs>
  )
}
