import * as React from "react"
import {
  closestCenter,
  DndContext,
  KeyboardSensor,
  MouseSensor,
  TouchSensor,
  useSensor,
  useSensors,
  type DragEndEvent,
} from "@dnd-kit/core"
import { restrictToVerticalAxis } from "@dnd-kit/modifiers"
import {
  arrayMove,
  SortableContext,
  sortableKeyboardCoordinates,
  useSortable,
  verticalListSortingStrategy,
} from "@dnd-kit/sortable"
import { CSS } from "@dnd-kit/utilities"
import {
  IconChevronDown,
  IconChevronLeft,
  IconChevronRight,
  IconChevronsLeft,
  IconChevronsRight,
  IconGripVertical,
  IconLayoutColumns,
  IconSearch,
  IconX,
} from "@tabler/icons-react"
import {
  columnVisibilityFeature,
  createColumnHelper,
  createPaginatedRowModel,
  createSortedRowModel,
  FlexRender,
  rowPaginationFeature,
  rowSelectionFeature,
  rowSortingFeature,
  sortFn_alphanumeric,
  sortFn_basic,
  sortFn_datetime,
  sortFn_text,
  tableFeatures,
  useTable,
  type ColumnDef,
  type ColumnVisibilityState,
  type Row,
  type RowData,
  type SortingState,
} from "@tanstack/react-table"

import { Button } from "@/components/ui/button"
import { Checkbox } from "@/components/ui/checkbox"
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import {
  Empty,
  EmptyContent,
  EmptyDescription,
  EmptyHeader,
  EmptyTitle,
} from "@/components/ui/empty"
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
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { cn } from "@/lib/utils"

export const fiturTabel = tableFeatures({
  columnVisibilityFeature,
  rowPaginationFeature,
  rowSelectionFeature,
  rowSortingFeature,
  paginatedRowModel: createPaginatedRowModel(),
  sortedRowModel: createSortedRowModel(),
  sortFns: {
    alphanumeric: sortFn_alphanumeric,
    basic: sortFn_basic,
    datetime: sortFn_datetime,
    text: sortFn_text,
  },
})

export type FiturTabel = typeof fiturTabel
export type KolomTabel<T extends RowData> = ColumnDef<FiturTabel, T, any>

export function pembantuKolom<T extends RowData>() {
  return createColumnHelper<FiturTabel, T>()
}

export interface FilterTabel<T> {
  id: string
  label: string
  opsi: { nilai: string; label: string }[]
  nilai: (baris: T) => string
}

interface DataTableProps<T extends { id: string }> {
  data: T[] | undefined
  columns: KolomTabel<T>[]
  /** Nama tabel untuk pembaca layar, mis. "Daftar berita" */
  label: string
  cari?: { placeholder: string; teks: (baris: T) => string }
  filter?: FilterTabel<T>[]
  /** Nilai awal filter, mis. dari parameter URL */
  filterAwal?: Record<string, string>
  tindakan?: React.ReactNode
  tindakanMassal?: (baris: T[], bersihkan: () => void) => React.ReactNode
  /** Bila diisi, baris bisa diseret untuk mengubah urutan tampil */
  urutkan?: (ids: string[]) => void
  kosong?: { judul: string; deskripsi?: string; tindakan?: React.ReactNode }
  memuat?: boolean
  ukuranHalaman?: number
  sortingAwal?: SortingState
  pilih?: boolean
  className?: string
}

function GagangSeret({ id, nonaktif }: { id: string; nonaktif: boolean }) {
  const { attributes, listeners } = useSortable({ id, disabled: nonaktif })
  return (
    <Button
      {...attributes}
      {...listeners}
      variant="ghost"
      size="icon"
      disabled={nonaktif}
      className="size-7 cursor-grab text-muted-foreground hover:bg-transparent active:cursor-grabbing disabled:cursor-not-allowed"
      title={nonaktif ? "Kosongkan pencarian & filter untuk mengubah urutan" : "Seret untuk mengubah urutan"}
    >
      <IconGripVertical className="size-3 text-muted-foreground" />
      <span className="sr-only">Seret untuk mengubah urutan</span>
    </Button>
  )
}

function BarisSeret<T extends { id: string }>({ row }: { row: Row<FiturTabel, T> }) {
  const { transform, transition, setNodeRef, isDragging } = useSortable({ id: row.original.id })
  return (
    <TableRow
      data-state={row.getIsSelected() && "selected"}
      data-dragging={isDragging}
      ref={setNodeRef}
      className="relative z-0 data-[dragging=true]:z-10 data-[dragging=true]:opacity-80"
      style={{ transform: CSS.Transform.toString(transform), transition }}
    >
      {row.getVisibleCells().map((cell) => (
        <TableCell key={cell.id}>
          <FlexRender cell={cell} />
        </TableCell>
      ))}
    </TableRow>
  )
}

export function DataTable<T extends { id: string }>({
  data,
  columns,
  label,
  cari,
  filter = [],
  filterAwal,
  tindakan,
  tindakanMassal,
  urutkan,
  kosong,
  memuat,
  ukuranHalaman = 10,
  sortingAwal = [],
  pilih = true,
  className,
}: DataTableProps<T>) {
  const [kueri, setKueri] = React.useState("")
  const [nilaiFilter, setNilaiFilter] = React.useState<Record<string, string>>(filterAwal ?? {})
  const [rowSelection, setRowSelection] = React.useState({})
  const [columnVisibility, setColumnVisibility] = React.useState<ColumnVisibilityState>({})
  const [sorting, setSorting] = React.useState<SortingState>(sortingAwal)
  const [pagination, setPagination] = React.useState({ pageIndex: 0, pageSize: ukuranHalaman })
  const sortableId = React.useId()
  const cariId = React.useId()

  React.useEffect(() => {
    if (filterAwal) setNilaiFilter(filterAwal)
  }, [filterAwal])

  const sensors = useSensors(
    useSensor(MouseSensor, {}),
    useSensor(TouchSensor, { activationConstraint: { delay: 150, tolerance: 5 } }),
    useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates })
  )

  const semua = React.useMemo(() => data ?? [], [data])
  const tersaring = React.useMemo(() => {
    const q = kueri.trim().toLowerCase()
    return semua.filter((baris) => {
      if (q && cari && !cari.teks(baris).toLowerCase().includes(q)) return false
      for (const f of filter) {
        const v = nilaiFilter[f.id]
        if (v && v !== "semua" && f.nilai(baris) !== v) return false
      }
      return true
    })
  }, [semua, kueri, cari, filter, nilaiFilter])

  const adaFilter = kueri.trim() !== "" || Object.values(nilaiFilter).some((v) => v && v !== "semua")
  const bisaSeret = !!urutkan
  const seretNonaktif = adaFilter || sorting.length > 0

  // reset ke halaman pertama saat filter berubah
  React.useEffect(() => {
    setPagination((p) => ({ ...p, pageIndex: 0 }))
  }, [kueri, nilaiFilter])

  const kolom = React.useMemo<KolomTabel<T>[]>(() => {
    const awal: KolomTabel<T>[] = []
    if (bisaSeret) {
      awal.push({
        id: "seret",
        header: () => <span className="sr-only">Urutan</span>,
        cell: ({ row }) => <GagangSeret id={row.original.id} nonaktif={seretNonaktif} />,
        enableSorting: false,
        enableHiding: false,
      })
    }
    if (pilih) {
      awal.push({
        id: "pilih",
        header: ({ table }) => (
          <div className="flex items-center justify-center">
            <Checkbox
              checked={
                table.getIsAllPageRowsSelected() ||
                (table.getIsSomePageRowsSelected() && "indeterminate")
              }
              onCheckedChange={(value) => table.toggleAllPageRowsSelected(!!value)}
              aria-label="Pilih semua baris di halaman ini"
            />
          </div>
        ),
        cell: ({ row }) => (
          <div className="flex items-center justify-center">
            <Checkbox
              checked={row.getIsSelected()}
              onCheckedChange={(value) => row.toggleSelected(!!value)}
              aria-label="Pilih baris"
            />
          </div>
        ),
        enableSorting: false,
        enableHiding: false,
      })
    }
    return [...awal, ...columns]
  }, [columns, bisaSeret, seretNonaktif, pilih])

  const table = useTable({
    features: fiturTabel,
    data: tersaring,
    columns: kolom,
    state: { sorting, columnVisibility, rowSelection, pagination },
    getRowId: (row) => row.id,
    enableRowSelection: pilih,
    onRowSelectionChange: setRowSelection,
    onSortingChange: setSorting,
    onColumnVisibilityChange: setColumnVisibility,
    onPaginationChange: setPagination,
  })

  const ids = React.useMemo(() => tersaring.map((x) => x.id), [tersaring])
  const terpilih = table.getSelectedRowModel().rows.map((r) => r.original)
  const bersihkan = () => setRowSelection({})

  function handleDragEnd(event: DragEndEvent) {
    const { active, over } = event
    if (!urutkan || !over || active.id === over.id) return
    const dari = ids.indexOf(String(active.id))
    const ke = ids.indexOf(String(over.id))
    if (dari < 0 || ke < 0) return
    urutkan(arrayMove(ids, dari, ke))
  }

  const kolomBisaDisembunyikan = table
    .getAllColumns()
    .filter((c) => typeof c.accessorFn !== "undefined" && c.getCanHide())

  const jumlahKolom = table.getVisibleLeafColumns().length

  return (
    <div className={cn("flex flex-col gap-4", className)}>
      <div className="flex flex-wrap items-center gap-2">
        {cari && (
          <div className="relative w-full sm:w-64">
            <Label htmlFor={cariId} className="sr-only">
              {cari.placeholder}
            </Label>
            <IconSearch className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              id={cariId}
              type="search"
              value={kueri}
              onChange={(e) => setKueri(e.target.value)}
              placeholder={cari.placeholder}
              className="h-8 pl-8"
            />
          </div>
        )}
        {filter.map((f) => (
          <Select
            key={f.id}
            value={nilaiFilter[f.id] ?? "semua"}
            onValueChange={(v) => setNilaiFilter((s) => ({ ...s, [f.id]: v }))}
          >
            <SelectTrigger size="sm" className="w-fit min-w-36" aria-label={f.label}>
              <SelectValue placeholder={f.label} />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="semua">{f.label}: semua</SelectItem>
              {f.opsi.map((o) => (
                <SelectItem key={o.nilai} value={o.nilai}>
                  {o.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        ))}
        {adaFilter && (
          <Button
            variant="ghost"
            size="sm"
            onClick={() => {
              setKueri("")
              setNilaiFilter({})
            }}
          >
            <IconX />
            Atur ulang
          </Button>
        )}
        <div className="ml-auto flex items-center gap-2">
          {kolomBisaDisembunyikan.length > 2 && (
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="outline" size="sm">
                  <IconLayoutColumns />
                  <span className="hidden lg:inline">Atur kolom</span>
                  <span className="lg:hidden">Kolom</span>
                  <IconChevronDown />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" className="w-56">
                <DropdownMenuLabel className="text-xs text-muted-foreground">
                  Tampilkan kolom
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                {kolomBisaDisembunyikan.map((column) => {
                  const header = column.columnDef.header
                  const nama =
                    typeof header === "string" ? header : column.id.charAt(0).toUpperCase() + column.id.slice(1)
                  return (
                    <DropdownMenuCheckboxItem
                      key={column.id}
                      checked={column.getIsVisible()}
                      onCheckedChange={(value) => column.toggleVisibility(!!value)}
                      onSelect={(e) => e.preventDefault()}
                    >
                      {nama}
                    </DropdownMenuCheckboxItem>
                  )
                })}
              </DropdownMenuContent>
            </DropdownMenu>
          )}
          {tindakan}
        </div>
      </div>

      {terpilih.length > 0 && tindakanMassal && (
        <div
          role="region"
          aria-label="Tindakan untuk baris terpilih"
          className="flex flex-wrap items-center gap-2 rounded-lg border bg-muted/50 px-3 py-2 text-sm"
        >
          <span className="font-medium tabular-nums">{terpilih.length} dipilih</span>
          <div className="ml-auto flex flex-wrap items-center gap-2">
            {tindakanMassal(terpilih, bersihkan)}
            <Button variant="ghost" size="sm" onClick={bersihkan}>
              Batal pilih
            </Button>
          </div>
        </div>
      )}

      <div className="overflow-hidden rounded-lg border">
        <DndContext
          collisionDetection={closestCenter}
          modifiers={[restrictToVerticalAxis]}
          onDragEnd={handleDragEnd}
          sensors={sensors}
          id={sortableId}
          accessibility={{
            screenReaderInstructions: {
              draggable:
                "Tekan spasi atau Enter untuk mengangkat baris, gunakan panah atas/bawah untuk memindahkan, lalu spasi atau Enter untuk melepas. Tekan Escape untuk batal.",
            },
          }}
        >
          <Table aria-label={label}>
            <TableHeader className="sticky top-0 z-10 bg-muted">
              {table.getHeaderGroups().map((headerGroup) => (
                <TableRow key={headerGroup.id}>
                  {headerGroup.headers.map((header) => (
                    <TableHead key={header.id} colSpan={header.colSpan}>
                      {header.isPlaceholder ? null : !header.column.accessorFn && !["pilih", "seret"].includes(header.column.id) ? (
                        <span className="sr-only">Tindakan</span>
                      ) : (
                        <FlexRender header={header} />
                      )}
                    </TableHead>
                  ))}
                </TableRow>
              ))}
            </TableHeader>
            <TableBody className={cn(bisaSeret && "**:data-[slot=table-cell]:first:w-8")}>
              {memuat ? (
                Array.from({ length: 4 }).map((_, i) => (
                  <TableRow key={i}>
                    <TableCell colSpan={jumlahKolom}>
                      <Skeleton className="h-6 w-full" />
                    </TableCell>
                  </TableRow>
                ))
              ) : table.getRowModel().rows.length ? (
                <SortableContext items={ids} strategy={verticalListSortingStrategy}>
                  {table.getRowModel().rows.map((row) => (
                    <BarisSeret key={row.id} row={row} />
                  ))}
                </SortableContext>
              ) : (
                <TableRow className="hover:bg-transparent">
                  <TableCell colSpan={jumlahKolom} className="p-0">
                    {semua.length === 0 && kosong ? (
                      <Empty className="py-10">
                        <EmptyHeader>
                          <EmptyTitle>{kosong.judul}</EmptyTitle>
                          {kosong.deskripsi && <EmptyDescription>{kosong.deskripsi}</EmptyDescription>}
                        </EmptyHeader>
                        {kosong.tindakan && <EmptyContent>{kosong.tindakan}</EmptyContent>}
                      </Empty>
                    ) : (
                      <p className="py-10 text-center text-sm text-muted-foreground">
                        Tidak ada yang cocok dengan pencarian atau filter.
                      </p>
                    )}
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </DndContext>
      </div>

      <div className="flex items-center justify-between gap-4 px-1">
        <div className="hidden flex-1 text-sm text-muted-foreground lg:flex">
          {pilih
            ? `${terpilih.length} dari ${tersaring.length} baris dipilih.`
            : `${tersaring.length} baris`}
          {bisaSeret && !seretNonaktif && tersaring.length > 1 && " Seret ⋮⋮ untuk mengubah urutan tampil."}
        </div>
        <div className="flex w-full items-center gap-6 lg:w-fit">
          <div className="hidden items-center gap-2 lg:flex">
            <Label htmlFor={`${sortableId}-baris`} className="text-sm font-medium">
              Baris per halaman
            </Label>
            <Select
              value={`${table.state.pagination.pageSize}`}
              onValueChange={(value) => table.setPageSize(Number(value))}
            >
              <SelectTrigger size="sm" className="w-20" id={`${sortableId}-baris`}>
                <SelectValue placeholder={table.state.pagination.pageSize} />
              </SelectTrigger>
              <SelectContent side="top">
                {[10, 20, 50, 100].map((n) => (
                  <SelectItem key={n} value={`${n}`}>
                    {n}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="flex w-fit items-center justify-center text-sm font-medium tabular-nums">
            Halaman {table.state.pagination.pageIndex + 1} dari {Math.max(1, table.getPageCount())}
          </div>
          <div className="ml-auto flex items-center gap-2 lg:ml-0">
            <Button
              variant="outline"
              className="hidden h-8 w-8 p-0 lg:flex"
              onClick={() => table.setPageIndex(0)}
              disabled={!table.getCanPreviousPage()}
            >
              <span className="sr-only">Halaman pertama</span>
              <IconChevronsLeft />
            </Button>
            <Button
              variant="outline"
              className="size-8"
              size="icon"
              onClick={() => table.previousPage()}
              disabled={!table.getCanPreviousPage()}
            >
              <span className="sr-only">Halaman sebelumnya</span>
              <IconChevronLeft />
            </Button>
            <Button
              variant="outline"
              className="size-8"
              size="icon"
              onClick={() => table.nextPage()}
              disabled={!table.getCanNextPage()}
            >
              <span className="sr-only">Halaman berikutnya</span>
              <IconChevronRight />
            </Button>
            <Button
              variant="outline"
              className="hidden size-8 lg:flex"
              size="icon"
              onClick={() => table.setPageIndex(table.getPageCount() - 1)}
              disabled={!table.getCanNextPage()}
            >
              <span className="sr-only">Halaman terakhir</span>
              <IconChevronsRight />
            </Button>
          </div>
        </div>
      </div>
    </div>
  )
}

/** Header kolom yang bisa diklik untuk mengurutkan */
export function HeaderUrut({
  column,
  judul,
  kanan,
}: {
  column: { getIsSorted: () => false | "asc" | "desc"; toggleSorting: (desc?: boolean) => void; getCanSort: () => boolean }
  judul: string
  kanan?: boolean
}) {
  const arah = column.getIsSorted()
  return (
    <Button
      variant="ghost"
      size="sm"
      className={cn("-ml-2 h-7 px-2 font-medium", kanan && "ml-auto")}
      onClick={() => column.toggleSorting(arah === "asc")}
    >
      {judul}
      <span aria-hidden="true" className="text-muted-foreground">
        {arah === "asc" ? "↑" : arah === "desc" ? "↓" : "↕"}
      </span>
      <span className="sr-only">
        {arah === "asc" ? ", urut naik" : arah === "desc" ? ", urut turun" : ", klik untuk mengurutkan"}
      </span>
    </Button>
  )
}
