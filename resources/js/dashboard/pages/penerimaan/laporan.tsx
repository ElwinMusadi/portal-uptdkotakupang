import * as React from "react"
import {
  IconAlertTriangle,
  IconCalendarEvent,
  IconCheck,
  IconChevronLeft,
  IconChevronRight,
  IconDownload,
  IconFileSpreadsheet,
  IconFilter,
  IconInfoCircle,
  IconTrendingDown,
  IconTrendingUp,
  IconUpload,
  IconX,
} from "@tabler/icons-react"
import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from "recharts"
import { toast } from "sonner"

import { useKonfirmasi } from "@/components/data/confirm"
import { DataTable, HeaderUrut, pembantuKolom } from "@/components/data/data-table"
import { Halaman, PengantarHalaman } from "@/components/data/page"
import { MenuBaris } from "@/components/data/row-actions"
import { NadaBadge } from "@/components/data/status-badge"
import { NavPenerimaan } from "@/components/penerimaan/nav-penerimaan"
import { RincianPenerimaan } from "@/components/penerimaan/rincian-penerimaan"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardAction, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { ChartContainer, ChartTooltip, ChartTooltipContent, type ChartConfig } from "@/components/ui/chart"
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from "@/components/ui/command"
import { DropdownMenuItem } from "@/components/ui/dropdown-menu"
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from "@/components/ui/empty"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover"
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from "@/components/ui/sheet"
import { Skeleton } from "@/components/ui/skeleton"
import { Spinner } from "@/components/ui/spinner"
import { Switch } from "@/components/ui/switch"
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { ToggleGroup, ToggleGroupItem } from "@/components/ui/toggle-group"
import { sesi } from "@/lib/auth"
import { angka, hariIniIso, rupiah, tanggal, tanggalJam, ukuranBerkas } from "@/lib/format"
import {
  bagianOpsen,
  bagianProvinsi,
  csvRincian,
  DIMENSI,
  KOMPONEN,
  LABEL_DIMENSI,
  labelNilai,
  MAKS_BERKAS,
  perubahan,
  unduhTeks,
  useDaftarImpor,
  useHapusImpor,
  useTampilImpor,
  useLaporanPenerimaan,
  useLoketPenerimaan,
  usePratinjauImpor,
  useSimpanImpor,
  type BarisRincian,
  type Dimensi,
  type ImporPenerimaan,
  type JenisLoket,
  type Jumlah,
  type LaporanPenerimaan,
  type PratinjauImpor,
  type Saringan,
} from "@/lib/penerimaan"
import { cn } from "@/lib/utils"

import {
  galatPeriode,
  gantiMode,
  geserPeriode,
  labelPeriode,
  periodeBulan,
  periodeDari,
  TANGGAL_ISO,
  type Mode,
  type Periode,
} from "../../../portal/penerimaan/periode"

/* ---------- Angka ---------- */

/** Porsi dalam persen; porsi yang ada tetapi di bawah 0,05% tidak dibulatkan menjadi "0,0%" */
function persen(bagian: number, total: number): string {
  if (total <= 0) return "—"
  const p = (bagian / total) * 100
  return p > 0 && p < 0.05 ? "<0,1%" : `${angka(p, 1)}%`
}

/** Rupiah ringkas untuk sumbu grafik, mis. "563 jt" */
function rupiahRingkas(n: number): string {
  if (n >= 1e9) return `${angka(n / 1e9, 1)} M`
  if (n >= 1e6) return `${angka(n / 1e6)} jt`
  if (n >= 1e3) return `${angka(n / 1e3)} rb`
  return angka(n)
}

const potong = (teks: string, maks: number) => (teks.length > maks ? `${teks.slice(0, maks - 1)}…` : teks)

const KONFIG_KOMPONEN = {
  pkb: { label: "PKB", color: "var(--chart-1)" },
  bbn: { label: "BBNKB", color: "var(--chart-3)" },
  denda: { label: "Denda", color: "var(--chart-5)" },
  tunggakan: { label: "Tunggakan", color: "var(--chart-4)" },
  opsen: { label: "Opsen", color: "var(--chart-2)" },
} satisfies ChartConfig

type KunciKomponen = keyof typeof KONFIG_KOMPONEN

function komponen(j: Jumlah): Record<KunciKomponen, number> {
  return Object.fromEntries(KOMPONEN.map((k) => [k.kunci, k.nilai(j)])) as Record<KunciKomponen, number>
}

function jumlahkan(baris: Jumlah[]): Jumlah {
  const total = { ...baris[0] }
  for (const b of baris.slice(1)) for (const kunci of Object.keys(total) as (keyof Jumlah)[]) total[kunci] += b[kunci]
  return total
}

/** Baris tooltip grafik: penanda warna, nama komponen, dan rupiahnya */
function barisRupiah(config: ChartConfig) {
  return function BarisTooltip(
    nilai: unknown,
    nama: unknown,
    item: { color?: string; payload?: { fill?: string } }
  ): React.ReactNode {
    return (
      <div className="flex w-full items-center gap-2">
        <span
          className="size-2.5 shrink-0 rounded-[2px]"
          style={{ background: item.color ?? item.payload?.fill }}
          aria-hidden="true"
        />
        <span className="flex-1 text-muted-foreground">{config[String(nama)]?.label ?? String(nama)}</span>
        <span className="font-mono font-medium text-foreground tabular-nums">{rupiah(Number(nilai))}</span>
      </div>
    )
  }
}

/* ---------- Halaman ---------- */

type Pilihan = Partial<Record<Dimensi, string[]>>

export default function HalamanPenerimaan() {
  const { data: daftarImpor, isLoading: memuatImpor } = useDaftarImpor()
  const [periodePilihan, setPeriode] = React.useState<Periode | null>(null)
  const [pilih, setPilih] = React.useState<Pilihan>({})
  const [tunggakan, setTunggakan] = React.useState<Saringan["tunggakan"]>("")
  const [impor, setImpor] = React.useState(false)
  const panel = React.useRef<HTMLDivElement>(null)

  // Sebelum pengguna memilih, laporan menampilkan hari terakhir yang sudah diimpor
  const terakhir = React.useMemo(
    () => (daftarImpor ? daftarImpor.reduce((m, i) => (i.tanggalAkhir > m ? i.tanggalAkhir : m), "") || hariIniIso() : null),
    [daftarImpor]
  )
  const periode: Periode | null = periodePilihan ?? (terakhir ? { mode: "harian", dari: terakhir, sampai: terakhir } : null)
  const galat = periode ? galatPeriode(periode) : null
  const dari = periode?.dari
  const sampai = periode?.sampai
  const saringan = React.useMemo<Saringan | null>(
    () => (dari && sampai && !galat ? { dari, sampai, pilih, tunggakan } : null),
    [dari, sampai, galat, pilih, tunggakan]
  )
  const { data: laporan, isError, error, isFetching, refetch } = useLaporanPenerimaan(saringan)
  const adaSaringan = Object.values(pilih).some((v) => v && v.length > 0) || tunggakan !== ""

  const ubahPilih = (d: Dimensi, nilai: string[]) =>
    setPilih((p) => {
      const baru = { ...p }
      if (nilai.length > 0) baru[d] = nilai
      else delete baru[d]
      return baru
    })

  const saring = (d: Dimensi | "tanggal", nilai: string) => {
    if (d === "tanggal") setPeriode({ mode: "harian", dari: nilai, sampai: nilai })
    else ubahPilih(d, [nilai])
    panel.current?.scrollIntoView({ behavior: "smooth", block: "start" })
  }

  const hapusSaringan = () => {
    setPilih({})
    setTunggakan("")
  }

  const tampilkanImpor = (i: ImporPenerimaan) => {
    setPeriode(periodeDari(i.tanggalAwal, i.tanggalAkhir))
    hapusSaringan()
    panel.current?.scrollIntoView({ behavior: "smooth", block: "start" })
  }

  const belumAdaImpor = daftarImpor?.length === 0

  return (
    <Halaman>
      <NavPenerimaan />
      <PengantarHalaman deskripsi="Penerimaan PKB, BBNKB, dan opsen per loket dari berkas STS sistem BPAD. Impor berkas harian atau bulanan setiap loket; laporan dihitung dari transaksinya. Nomor polisi, nama pemilik, dan RT/RW hanya disimpan 7 hari untuk Rincian penerimaan dan tidak pernah tampil di portal.">
        <Button onClick={() => setImpor(true)}>
          <IconUpload />
          Impor berkas STS
        </Button>
      </PengantarHalaman>

      {belumAdaImpor ? (
        <Card>
          <CardContent>
            <Empty>
              <EmptyHeader>
                <EmptyMedia variant="icon">
                  <IconFileSpreadsheet />
                </EmptyMedia>
                <EmptyTitle>Belum ada berkas STS yang diimpor</EmptyTitle>
                <EmptyDescription>
                  Ekspor STS harian atau bulanan dari sistem BPAD (berkas .xls), lalu impor di sini untuk setiap loket.
                </EmptyDescription>
              </EmptyHeader>
              <EmptyContent>
                <Button onClick={() => setImpor(true)}>
                  <IconUpload />
                  Impor berkas STS
                </Button>
              </EmptyContent>
            </Empty>
          </CardContent>
        </Card>
      ) : (
        <>
          <div ref={panel} className="scroll-mt-20">
            {periode ? (
              <PanelSaringan
                periode={periode}
                ubahPeriode={setPeriode}
                galat={galat}
                laporan={laporan}
                pilih={pilih}
                ubahPilih={ubahPilih}
                tunggakan={tunggakan}
                ubahTunggakan={setTunggakan}
                adaSaringan={adaSaringan}
                hapusSaringan={hapusSaringan}
              />
            ) : (
              <Skeleton className="h-30 rounded-xl" />
            )}
          </div>

          {isError ? (
            <Alert variant="destructive">
              <IconAlertTriangle />
              <AlertTitle>Laporan gagal dimuat</AlertTitle>
              <AlertDescription>
                <p>{(error as Error).message}</p>
                <Button variant="outline" size="sm" className="mt-2" onClick={() => void refetch()}>
                  Coba lagi
                </Button>
              </AlertDescription>
            </Alert>
          ) : !laporan ? (
            galat ? null : <KerangkaLaporan />
          ) : (
            <div
              className={cn("grid gap-4 transition-opacity md:gap-6", isFetching && "opacity-60")}
              aria-busy={isFetching}
            >
              <Ringkasan laporan={laporan} />
              <PerLoket laporan={laporan} adaSaringan={adaSaringan} />
              {laporan.ringkasan.transaksi === 0 ? (
                <Card>
                  <CardContent>
                    <Empty>
                      <EmptyHeader>
                        <EmptyTitle>Tidak ada transaksi</EmptyTitle>
                        <EmptyDescription>
                          {adaSaringan
                            ? "Tidak ada transaksi yang cocok dengan saringan pada periode ini."
                            : "Belum ada berkas STS yang diimpor untuk periode ini."}
                        </EmptyDescription>
                      </EmptyHeader>
                      {adaSaringan && (
                        <EmptyContent>
                          <Button variant="outline" onClick={hapusSaringan}>
                            Hapus saringan
                          </Button>
                        </EmptyContent>
                      )}
                    </Empty>
                  </CardContent>
                </Card>
              ) : (
                <>
                  {laporan.dari !== laporan.sampai && <GrafikHarian laporan={laporan} bukaHari={(h) => saring("tanggal", h)} />}
                  <div className="grid gap-4 md:gap-6 xl:grid-cols-2">
                    <GrafikRincian laporan={laporan} dimensi="jenisDaftar" judul="Per jenis daftar" />
                    <GrafikRincian laporan={laporan} dimensi="jenisKendaraan" judul="Per jenis kendaraan" />
                    <GrafikRincian
                      laporan={laporan}
                      dimensi="kecamatan"
                      judul="Per kecamatan"
                      deskripsi="Menurut alamat pemilik kendaraan."
                    />
                    <GrafikRincian laporan={laporan} dimensi="uptAsal" judul="Per asal UPT" deskripsi="UPT tempat kendaraan terdaftar." />
                  </div>
                  <TabelRincian laporan={laporan} saring={saring} />
                  {saringan && <RincianPenerimaan saringan={saringan} />}
                </>
              )}
            </div>
          )}
        </>
      )}

      <RiwayatImpor data={daftarImpor} memuat={memuatImpor} lihat={tampilkanImpor} />
      {impor && <SheetImpor tutup={() => setImpor(false)} selesai={tampilkanImpor} />}
    </Halaman>
  )
}

function KerangkaLaporan() {
  return (
    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4" role="status">
      <span className="sr-only">Memuat laporan…</span>
      <Skeleton className="h-65 rounded-xl md:col-span-2 xl:row-span-2" />
      {Array.from({ length: 4 }).map((_, i) => (
        <Skeleton key={i} className="h-30.5 rounded-xl" />
      ))}
    </div>
  )
}

/* ---------- Periode dan saringan ---------- */

function PanelSaringan({
  periode,
  ubahPeriode,
  galat,
  laporan,
  pilih,
  ubahPilih,
  tunggakan,
  ubahTunggakan,
  adaSaringan,
  hapusSaringan,
}: {
  periode: Periode
  ubahPeriode: (p: Periode) => void
  galat: string | null
  laporan: LaporanPenerimaan | undefined
  pilih: Pilihan
  ubahPilih: (d: Dimensi, nilai: string[]) => void
  tunggakan: Saringan["tunggakan"]
  ubahTunggakan: (t: Saringan["tunggakan"]) => void
  adaSaringan: boolean
  hapusSaringan: () => void
}) {
  const hariIni = hariIniIso()
  const galatId = React.useId()

  const opsi = (d: Dimensi): { nilai: string; label: string }[] => {
    if (!laporan) return []
    if (d === "loket") return laporan.pilihan.loket.map((l) => ({ nilai: l.nilai, label: l.nama }))
    return laporan.pilihan[d].map((nilai) => ({ nilai, label: labelNilai(d, { nilai }) }))
  }

  return (
    <Card className="gap-4 py-4">
      <CardContent className="grid gap-4 px-4">
        <div className="flex flex-wrap items-center gap-2">
          <ToggleGroup
            type="single"
            variant="outline"
            value={periode.mode}
            onValueChange={(m) => m && ubahPeriode(gantiMode(periode, m as Mode, hariIni))}
            aria-label="Jenis laporan"
          >
            <ToggleGroupItem value="harian" className="px-3">
              Harian
            </ToggleGroupItem>
            <ToggleGroupItem value="bulanan" className="px-3">
              Bulanan
            </ToggleGroupItem>
            <ToggleGroupItem value="rentang" className="px-3">
              Rentang
            </ToggleGroupItem>
          </ToggleGroup>

          {periode.mode === "rentang" ? (
            <div className="flex flex-wrap items-center gap-2">
              <Input
                type="date"
                aria-label="Dari tanggal"
                aria-invalid={!!galat}
                aria-describedby={galat ? galatId : undefined}
                value={periode.dari}
                max={hariIni}
                onChange={(e) => ubahPeriode({ ...periode, dari: e.target.value })}
                className="w-auto"
              />
              <span className="text-sm text-muted-foreground">s.d.</span>
              <Input
                type="date"
                aria-label="Sampai tanggal"
                aria-invalid={!!galat}
                aria-describedby={galat ? galatId : undefined}
                value={periode.sampai}
                max={hariIni}
                onChange={(e) => ubahPeriode({ ...periode, sampai: e.target.value })}
                className="w-auto"
              />
            </div>
          ) : (
            <div className="flex items-center gap-1">
              <Button
                variant="outline"
                size="icon"
                aria-label={periode.mode === "harian" ? "Hari sebelumnya" : "Bulan sebelumnya"}
                disabled={!!galat}
                onClick={() => ubahPeriode(geserPeriode(periode, -1))}
              >
                <IconChevronLeft />
              </Button>
              {periode.mode === "harian" ? (
                <Input
                  type="date"
                  aria-label="Tanggal laporan"
                  aria-invalid={!!galat}
                  aria-describedby={galat ? galatId : undefined}
                  value={periode.dari}
                  max={hariIni}
                  onChange={(e) => ubahPeriode({ mode: "harian", dari: e.target.value, sampai: e.target.value })}
                  className="w-auto"
                />
              ) : (
                <Input
                  type="month"
                  aria-label="Bulan laporan"
                  value={periode.dari.slice(0, 7)}
                  max={hariIni.slice(0, 7)}
                  onChange={(e) => e.target.value && ubahPeriode(periodeBulan(e.target.value))}
                  className="w-auto"
                />
              )}
              <Button
                variant="outline"
                size="icon"
                aria-label={periode.mode === "harian" ? "Hari berikutnya" : "Bulan berikutnya"}
                disabled={!!galat || periode.sampai >= hariIni}
                onClick={() => ubahPeriode(geserPeriode(periode, 1))}
              >
                <IconChevronRight />
              </Button>
            </div>
          )}
          {!galat && (
            <p className="text-sm font-medium sm:ml-auto" aria-live="polite">
              {labelPeriode(periode.dari, periode.sampai)}
            </p>
          )}
        </div>
        {galat && (
          <p id={galatId} role="alert" className="text-sm text-destructive">
            {galat}
          </p>
        )}

        <div className="flex flex-wrap items-center gap-2">
          <IconFilter className="size-4 text-muted-foreground" aria-hidden="true" />
          <span className="sr-only">Saringan:</span>
          {DIMENSI.map((d) => (
            <PilihSaringan key={d} dimensi={d} opsi={opsi(d)} terpilih={pilih[d] ?? []} ubah={(nilai) => ubahPilih(d, nilai)} />
          ))}
          <Select value={tunggakan || "semua"} onValueChange={(v) => ubahTunggakan(v === "semua" ? "" : (v as "ada" | "tidak"))}>
            <SelectTrigger size="sm" aria-label="Saringan tunggakan" className={cn(tunggakan && "border-primary/40 bg-primary/5")}>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="semua">Tunggakan: semua</SelectItem>
              <SelectItem value="ada">Ada tunggakan</SelectItem>
              <SelectItem value="tidak">Tanpa tunggakan</SelectItem>
            </SelectContent>
          </Select>
          {adaSaringan && (
            <Button variant="ghost" size="sm" onClick={hapusSaringan}>
              <IconX />
              Hapus saringan
            </Button>
          )}
        </div>
      </CardContent>
    </Card>
  )
}

function PilihSaringan({
  dimensi,
  opsi,
  terpilih,
  ubah,
}: {
  dimensi: Dimensi
  opsi: { nilai: string; label: string }[]
  terpilih: string[]
  ubah: (nilai: string[]) => void
}) {
  const label = LABEL_DIMENSI[dimensi]
  // Nilai terpilih yang tidak ada di periode ini tetap tampil agar bisa dilepas
  const semua = [
    ...opsi,
    ...terpilih.filter((t) => !opsi.some((o) => o.nilai === t)).map((t) => ({ nilai: t, label: labelNilai(dimensi, { nilai: t }) })),
  ]
  const ringkas =
    terpilih.length === 1 ? potong(semua.find((o) => o.nilai === terpilih[0])?.label ?? terpilih[0], 24) : `${terpilih.length} dipilih`

  return (
    <Popover>
      <PopoverTrigger asChild>
        <Button
          variant="outline"
          size="sm"
          className={cn("border-dashed", terpilih.length > 0 && "border-solid border-primary/40 bg-primary/5")}
        >
          {label}
          {terpilih.length > 0 && (
            <Badge variant="secondary" className="rounded-sm px-1 font-normal">
              {ringkas}
            </Badge>
          )}
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-72 p-0" align="start">
        <Command>
          <CommandInput placeholder={`Cari ${label.toLowerCase()}…`} />
          <CommandList>
            <CommandEmpty>Tidak ada pada periode ini.</CommandEmpty>
            <CommandGroup>
              {semua.map((o) => {
                const dipilih = terpilih.includes(o.nilai)
                return (
                  <CommandItem
                    key={o.nilai}
                    value={`${o.label} ${o.nilai}`}
                    onSelect={() => ubah(dipilih ? terpilih.filter((t) => t !== o.nilai) : [...terpilih, o.nilai])}
                  >
                    <span
                      className={cn(
                        "flex size-4 shrink-0 items-center justify-center rounded-[4px] border",
                        dipilih ? "border-primary bg-primary text-primary-foreground" : "border-input"
                      )}
                      aria-hidden="true"
                    >
                      {dipilih && <IconCheck className="size-3 text-current" />}
                    </span>
                    <span className="truncate">{o.label}</span>
                    {dipilih && <span className="sr-only">(dipilih)</span>}
                  </CommandItem>
                )
              })}
            </CommandGroup>
          </CommandList>
          {terpilih.length > 0 && (
            <div className="border-t p-1">
              <Button variant="ghost" size="sm" className="w-full" onClick={() => ubah([])}>
                Lepas semua pilihan
              </Button>
            </div>
          )}
        </Command>
      </PopoverContent>
    </Popover>
  )
}

/* ---------- Ringkasan (bento) ---------- */

function BadgePerubahan({ nilai }: { nilai: number | null }) {
  if (nilai === null) {
    return (
      <Badge variant="outline" className="text-muted-foreground">
        Tanpa pembanding
      </Badge>
    )
  }
  const naik = nilai >= 0
  return (
    <Badge variant="outline">
      {naik ? <IconTrendingUp /> : <IconTrendingDown />}
      {naik ? "+" : ""}
      {angka(nilai, Math.abs(nilai) < 10 ? 1 : 0)}%<span className="sr-only"> dibanding periode pembanding</span>
    </Badge>
  )
}

/** Angka besar dengan lencananya; lencana turun ke bawah angka bila tempatnya tidak cukup (rupiah miliaran) */
function AngkaBerlencana({ nilai, lencana, className }: { nilai: string; lencana?: React.ReactNode; className?: string }) {
  return (
    <div className="flex flex-wrap items-center gap-x-3 gap-y-1.5">
      <CardTitle className={cn("tabular-nums", className)}>{nilai}</CardTitle>
      {lencana}
    </div>
  )
}

function KartuAngka({
  judul,
  nilai,
  lencana,
  catatan,
}: {
  judul: string
  nilai: string
  lencana?: React.ReactNode
  catatan: React.ReactNode
}) {
  return (
    <Card className="@container/card gap-3">
      <CardHeader>
        <CardDescription>{judul}</CardDescription>
        <AngkaBerlencana nilai={nilai} lencana={lencana} className="text-xl @[250px]/card:text-2xl" />
      </CardHeader>
      <CardFooter className="text-sm text-muted-foreground">{catatan}</CardFooter>
    </Card>
  )
}

function Ringkasan({ laporan }: { laporan: LaporanPenerimaan }) {
  const r = laporan.ringkasan
  const p = laporan.pembanding
  const provinsi = bagianProvinsi(r)
  const opsen = bagianOpsen(r)
  const susunan = KOMPONEN.map((k) => ({
    ...k,
    rp: k.nilai(r),
    warna: KONFIG_KOMPONEN[k.kunci as KunciKomponen].color,
  }))
  const namaPembanding = laporan.dari === laporan.sampai ? "Hari layanan sebelumnya" : "Periode sebelumnya"
  const baru = laporan.rincian.jenisDaftar.find((b) => b.nilai.toUpperCase() === "BARU")

  return (
    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <Card className="bg-gradient-to-t from-primary/5 to-card shadow-xs md:col-span-2 xl:row-span-2 dark:bg-card">
        <CardHeader>
          <CardDescription>Total penerimaan · {labelPeriode(laporan.dari, laporan.sampai)}</CardDescription>
          <AngkaBerlencana
            nilai={rupiah(r.jumlah)}
            lencana={<BadgePerubahan nilai={perubahan(r.jumlah, p?.ringkasan.jumlah)} />}
            className="text-3xl md:text-4xl"
          />
        </CardHeader>
        <CardContent className="grid gap-4">
          <div
            className="flex h-3 overflow-hidden rounded-full bg-muted"
            role="img"
            aria-label={`Susunan penerimaan: ${susunan.map((k) => `${k.label} ${persen(k.rp, r.jumlah)}`).join(", ")}`}
          >
            {r.jumlah > 0 &&
              susunan
                .filter((k) => k.rp > 0)
                .map((k) => <div key={k.kunci} style={{ width: `${(k.rp / r.jumlah) * 100}%`, background: k.warna }} />)}
          </div>
          <dl className="grid grid-cols-2 gap-x-6 gap-y-3 sm:grid-cols-3">
            {susunan.map((k) => (
              <div key={k.kunci} className="grid gap-0.5">
                <dt className="flex items-center gap-1.5 text-xs text-muted-foreground">
                  <span className="size-2.5 shrink-0 rounded-[2px]" style={{ background: k.warna }} aria-hidden="true" />
                  {k.label}
                </dt>
                <dd className="font-medium tabular-nums">{rupiah(k.rp)}</dd>
              </div>
            ))}
          </dl>
        </CardContent>
        <CardFooter className="text-sm text-muted-foreground">
          {!p
            ? "Belum ada periode sebelumnya untuk dibandingkan."
            : p.ringkasan.transaksi === 0
              ? `${namaPembanding} (${labelPeriode(p.dari, p.sampai)}): belum ada data.`
              : `${namaPembanding} (${labelPeriode(p.dari, p.sampai)}): ${rupiah(p.ringkasan.jumlah)}`}
        </CardFooter>
      </Card>
      <KartuAngka
        judul="Transaksi"
        nilai={angka(r.transaksi)}
        lencana={<BadgePerubahan nilai={perubahan(r.transaksi, p?.ringkasan.transaksi)} />}
        catatan={p && p.ringkasan.transaksi > 0 ? `${angka(p.ringkasan.transaksi)} pada periode pembanding` : "Belum ada data pembanding"}
      />
      <KartuAngka
        judul="Kendaraan baru"
        nilai={angka(baru?.transaksi ?? 0)}
        lencana={<Badge variant="outline">{persen(baru?.jumlah ?? 0, r.jumlah)}</Badge>}
        catatan={`${rupiah(baru?.jumlah ?? 0)} dari jenis daftar Baru`}
      />
      <KartuAngka
        judul="Pajak provinsi"
        nilai={rupiah(provinsi)}
        lencana={<Badge variant="outline">{persen(provinsi, r.jumlah)}</Badge>}
        catatan="PKB, BBNKB, denda, dan tunggakan"
      />
      <KartuAngka
        judul="Opsen kabupaten/kota"
        nilai={rupiah(opsen)}
        lencana={<Badge variant="outline">{persen(opsen, r.jumlah)}</Badge>}
        catatan="Untuk kabupaten/kota asal kendaraan"
      />
    </div>
  )
}

/* ---------- Per loket ---------- */

function PerLoket({ laporan, adaSaringan }: { laporan: LaporanPenerimaan; adaSaringan: boolean }) {
  const baris = laporan.rincian.loket
  const total = laporan.ringkasan.jumlah
  // Loket yang melayani hari itu tetapi belum diimpor; tidak berarti apa-apa bila laporan disaring
  const belum = adaSaringan ? [] : laporan.loketBuka.filter((l) => !baris.some((b) => b.nilai === l.kunci))
  if (baris.length === 0 && belum.length === 0) return null

  return (
    <section aria-labelledby="judul-per-loket" className="grid gap-3">
      <h2 id="judul-per-loket" className="text-base font-heading font-(--fw-heading)">
        Per loket
      </h2>
      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {baris.map((b) => (
          <Card key={b.nilai} className="gap-3 py-4 shadow-xs">
            <CardHeader className="px-4">
              <CardDescription>{labelNilai("loket", b)}</CardDescription>
              <AngkaBerlencana
                nilai={rupiah(b.jumlah)}
                lencana={<Badge variant="outline">{persen(b.jumlah, total)}</Badge>}
                className="text-xl"
              />
            </CardHeader>
            <CardContent className="grid gap-2 px-4">
              <div className="h-1.5 overflow-hidden rounded-full bg-muted" aria-hidden="true">
                <div className="h-full rounded-full bg-primary" style={{ width: total > 0 ? `${(b.jumlah / total) * 100}%` : 0 }} />
              </div>
              <p className="text-xs text-muted-foreground">{angka(b.transaksi)} transaksi</p>
            </CardContent>
          </Card>
        ))}
        {belum.map((l) => (
          <Card key={l.kunci} className="gap-3 border-dashed py-4 shadow-none">
            <CardHeader className="px-4">
              <CardDescription>{l.nama}</CardDescription>
              <CardTitle className="text-base font-medium text-muted-foreground">Belum ada data</CardTitle>
            </CardHeader>
            <CardContent className="px-4 text-xs text-muted-foreground">
              Loket ini melayani pada tanggal tersebut menurut jadwal. Impor berkas STS-nya agar masuk laporan.
            </CardContent>
          </Card>
        ))}
      </div>
    </section>
  )
}

/* ---------- Grafik ---------- */

const KONFIG_HARIAN = { jumlah: { label: "Penerimaan", color: "var(--chart-1)" } } satisfies ChartConfig

const fmtTanggalSumbu = new Intl.DateTimeFormat("id-ID", { day: "numeric", month: "short", timeZone: "UTC" })

function GrafikHarian({ laporan, bukaHari }: { laporan: LaporanPenerimaan; bukaHari: (hari: string) => void }) {
  const data = laporan.rincian.tanggal.map((b) => ({ tanggal: b.nilai, jumlah: b.jumlah, transaksi: b.transaksi }))
  return (
    <Card className="shadow-xs">
      <CardHeader>
        <CardTitle className="text-base">Penerimaan per hari</CardTitle>
        <CardDescription>
          {angka(data.length)} hari berisi transaksi. Pilih batang untuk membuka laporan harian.
        </CardDescription>
      </CardHeader>
      <CardContent className="px-2 sm:px-6">
        <ChartContainer config={KONFIG_HARIAN} className="aspect-auto h-60 w-full">
          <BarChart
            data={data}
            margin={{ left: 4, right: 4 }}
            onClick={(state) => {
              const hari = state?.activeLabel
              if (typeof hari === "string" && TANGGAL_ISO.test(hari)) bukaHari(hari)
            }}
            className="cursor-pointer"
          >
            <CartesianGrid vertical={false} />
            <XAxis
              dataKey="tanggal"
              tickLine={false}
              axisLine={false}
              tickMargin={8}
              minTickGap={16}
              tickFormatter={(v: string) => fmtTanggalSumbu.format(new Date(`${v}T00:00:00Z`))}
            />
            <YAxis tickLine={false} axisLine={false} width={56} tickFormatter={(v: number) => rupiahRingkas(v)} />
            <ChartTooltip
              cursor={false}
              content={
                <ChartTooltipContent
                  labelFormatter={(_, payload) => {
                    const titik = payload?.[0]?.payload as { tanggal: string; transaksi: number } | undefined
                    return titik ? `${tanggal(titik.tanggal, "lengkap")} · ${angka(titik.transaksi)} transaksi` : ""
                  }}
                  formatter={barisRupiah(KONFIG_HARIAN)}
                />
              }
            />
            <Bar dataKey="jumlah" fill="var(--color-jumlah)" radius={4} />
          </BarChart>
        </ChartContainer>
      </CardContent>
    </Card>
  )
}

function GrafikRincian({
  laporan,
  dimensi,
  judul,
  deskripsi,
  batas = 8,
}: {
  laporan: LaporanPenerimaan
  dimensi: Dimensi
  judul: string
  deskripsi?: string
  batas?: number
}) {
  const baris = laporan.rincian[dimensi]
  const data = React.useMemo(() => {
    const titik = (label: string, j: Jumlah) => ({ label, jumlah: j.jumlah, transaksi: j.transaksi, ...komponen(j) })
    const atas = baris.slice(0, batas).map((b) => titik(labelNilai(dimensi, b), b))
    const sisa = baris.slice(batas)
    return sisa.length > 0 ? [...atas, titik(`${angka(sisa.length)} lainnya`, jumlahkan(sisa))] : atas
  }, [baris, batas, dimensi])

  return (
    <Card className="shadow-xs">
      <CardHeader>
        <CardTitle className="text-base">{judul}</CardTitle>
        <CardDescription>
          {deskripsi ? `${deskripsi} ` : ""}
          {baris.length > batas ? `${batas} terbesar dari ${angka(baris.length)}.` : `${angka(baris.length)} kelompok.`}
        </CardDescription>
      </CardHeader>
      <CardContent className="grid gap-3 px-2 sm:px-6">
        <ChartContainer config={KONFIG_KOMPONEN} className="aspect-auto w-full" style={{ height: data.length * 34 + 12 }}>
          <BarChart data={data} layout="vertical" margin={{ left: 0, right: 8 }} barCategoryGap={6}>
            <CartesianGrid horizontal={false} />
            <YAxis
              dataKey="label"
              type="category"
              tickLine={false}
              axisLine={false}
              width={148}
              tickFormatter={(v: string) => potong(v, 22)}
            />
            <XAxis type="number" hide />
            <ChartTooltip
              cursor={{ fill: "var(--muted)", opacity: 0.5 }}
              content={
                <ChartTooltipContent
                  labelFormatter={(_, payload) => {
                    const titik = payload?.[0]?.payload as { label: string; jumlah: number; transaksi: number } | undefined
                    return titik ? (
                      <div className="grid gap-0.5">
                        <span>{titik.label}</span>
                        <span className="font-normal text-muted-foreground">
                          {rupiah(titik.jumlah)} · {angka(titik.transaksi)} transaksi
                        </span>
                      </div>
                    ) : null
                  }}
                  formatter={barisRupiah(KONFIG_KOMPONEN)}
                />
              }
            />
            {KOMPONEN.map((k) => (
              <Bar key={k.kunci} dataKey={k.kunci} stackId="komponen" fill={`var(--color-${k.kunci})`} />
            ))}
          </BarChart>
        </ChartContainer>
        <ul className="flex flex-wrap gap-x-4 gap-y-1 px-2 text-xs text-muted-foreground sm:px-0" aria-label="Keterangan warna">
          {KOMPONEN.map((k) => (
            <li key={k.kunci} className="flex items-center gap-1.5">
              <span
                className="size-2.5 rounded-[2px]"
                style={{ background: KONFIG_KOMPONEN[k.kunci as KunciKomponen].color }}
                aria-hidden="true"
              />
              {k.label}
            </li>
          ))}
        </ul>
      </CardContent>
    </Card>
  )
}

/* ---------- Tabel rincian ---------- */

const TAB_RINCIAN: (Dimensi | "tanggal")[] = [
  "jenisDaftar",
  "jenisKendaraan",
  "kepemilikan",
  "warnaPlat",
  "merk",
  "uptAsal",
  "kecamatan",
  "kelurahan",
  "loket",
  "tanggal",
]

const judulRincian = (d: Dimensi | "tanggal") => (d === "tanggal" ? "Tanggal" : LABEL_DIMENSI[d])

function TabelRincian({ laporan, saring }: { laporan: LaporanPenerimaan; saring: (d: Dimensi | "tanggal", nilai: string) => void }) {
  const [pilihan, setPilihan] = React.useState<Dimensi | "tanggal">("jenisDaftar")
  const tab = laporan.dari === laporan.sampai ? TAB_RINCIAN.filter((t) => t !== "tanggal") : TAB_RINCIAN
  const dimensi = tab.includes(pilihan) ? pilihan : "jenisDaftar"
  const baris = laporan.rincian[dimensi]
  const total = laporan.ringkasan
  const kelurahan = dimensi === "kelurahan"

  const ekspor = () => {
    const periode = laporan.dari === laporan.sampai ? laporan.dari : `${laporan.dari}_sd_${laporan.sampai}`
    const nama = judulRincian(dimensi).toLowerCase().replace(/\s+/g, "-")
    unduhTeks(csvRincian(dimensi, baris, total), `penerimaan-per-${nama}-${periode}.csv`)
  }

  const tampil = (b: BarisRincian) => (dimensi === "tanggal" ? tanggal(b.nilai) : labelNilai(dimensi, b))

  return (
    <Card className="shadow-xs">
      <CardHeader>
        <CardTitle className="text-base">Rekap penerimaan</CardTitle>
        <CardDescription>Rupiah setiap komponen. Pilih nama di kolom pertama untuk menyaring laporan dengannya.</CardDescription>
        <CardAction>
          <Button variant="outline" size="sm" onClick={ekspor}>
            <IconDownload />
            Ekspor CSV
          </Button>
        </CardAction>
      </CardHeader>
      <CardContent className="grid gap-4">
        <Tabs value={dimensi} onValueChange={(v) => setPilihan(v as Dimensi | "tanggal")} className="min-w-0">
          {/* Di layar sempit deretan tab digeser ke samping, bukan dilipat */}
          <div className="-mx-1 max-w-full overflow-x-auto px-1 pb-1">
            <TabsList>
              {tab.map((t) => (
                <TabsTrigger key={t} value={t} className="flex-none">
                  {judulRincian(t)}
                </TabsTrigger>
              ))}
            </TabsList>
          </div>
        </Tabs>
        <div className="max-h-120 overflow-auto rounded-lg border">
          <Table aria-label={`Rekap penerimaan per ${judulRincian(dimensi).toLowerCase()}`}>
            <TableHeader className="sticky top-0 z-10 bg-muted">
              <TableRow>
                <TableHead>{judulRincian(dimensi)}</TableHead>
                {kelurahan && <TableHead>Kecamatan</TableHead>}
                <TableHead className="text-right">Transaksi</TableHead>
                {KOMPONEN.map((k) => (
                  <TableHead key={k.kunci} className="text-right">
                    {k.label}
                  </TableHead>
                ))}
                <TableHead className="text-right">Jumlah</TableHead>
                <TableHead className="text-right">Porsi</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {baris.map((b) => (
                <TableRow key={`${b.nilai}|${b.kecamatan ?? ""}`}>
                  <TableCell className="font-medium">
                    <button
                      type="button"
                      onClick={() => saring(dimensi, b.nilai)}
                      className="max-w-64 truncate rounded-sm text-left underline-offset-4 hover:underline focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                      title={dimensi === "tanggal" ? "Buka laporan harian tanggal ini" : "Saring laporan dengan nilai ini"}
                    >
                      {tampil(b)}
                    </button>
                  </TableCell>
                  {kelurahan && <TableCell className="text-muted-foreground">{b.kecamatan}</TableCell>}
                  <TableCell className="text-right tabular-nums">{angka(b.transaksi)}</TableCell>
                  {KOMPONEN.map((k) => (
                    <TableCell key={k.kunci} className="text-right tabular-nums">
                      {angka(k.nilai(b))}
                    </TableCell>
                  ))}
                  <TableCell className="text-right font-medium tabular-nums">{angka(b.jumlah)}</TableCell>
                  <TableCell className="text-right text-muted-foreground tabular-nums">{persen(b.jumlah, total.jumlah)}</TableCell>
                </TableRow>
              ))}
            </TableBody>
            <TableFooter className="sticky bottom-0">
              <TableRow>
                <TableCell colSpan={kelurahan ? 2 : 1}>Jumlah</TableCell>
                <TableCell className="text-right tabular-nums">{angka(total.transaksi)}</TableCell>
                {KOMPONEN.map((k) => (
                  <TableCell key={k.kunci} className="text-right tabular-nums">
                    {angka(k.nilai(total))}
                  </TableCell>
                ))}
                <TableCell className="text-right tabular-nums">{angka(total.jumlah)}</TableCell>
                <TableCell className="text-right tabular-nums">{total.jumlah > 0 ? "100%" : "—"}</TableCell>
              </TableRow>
            </TableFooter>
          </Table>
        </div>
      </CardContent>
    </Card>
  )
}

/* ---------- Riwayat impor ---------- */

const ki = pembantuKolom<ImporPenerimaan>()

const judulImpor = (i: ImporPenerimaan) => `${i.namaLoket} · ${labelPeriode(i.tanggalAwal, i.tanggalAkhir)}`

function RiwayatImpor({
  data,
  memuat,
  lihat,
}: {
  data: ImporPenerimaan[] | undefined
  memuat: boolean
  lihat: (i: ImporPenerimaan) => void
}) {
  const hapus = useHapusImpor()
  const tampil = useTampilImpor()
  const konfirmasi = useKonfirmasi()
  // Hanya Administrator yang menampilkan impor di portal (D-048); server juga menolaknya
  const administrator = sesi().peran === "administrator"

  const ubahTampil = async (impor: ImporPenerimaan, nyala: boolean) => {
    if (nyala) {
      const ya = await konfirmasi({
        judul: `Tampilkan ${judulImpor(impor)} di portal?`,
        deskripsi:
          "Penerimaannya tampil untuk umum di halaman Laporan Penerimaan portal, tanpa identitas wajib pajak. Kelompok dengan kurang dari 3 transaksi disamarkan. Pastikan berkasnya sudah diperiksa.",
        label: "Tampilkan",
      })
      if (!ya) return
    }
    try {
      await tampil.mutateAsync({ id: impor.id, tampil: nyala })
      toast.success(nyala ? "Impor tampil di portal" : "Impor disembunyikan dari portal")
    } catch (e) {
      toast.error("Tampilan di portal gagal diubah", { description: (e as Error).message })
    }
  }

  const hapusImpor = async (daftar: ImporPenerimaan[]) => {
    const ya = await konfirmasi({
      judul: daftar.length === 1 ? `Hapus impor ${judulImpor(daftar[0])}?` : `Hapus ${daftar.length} impor?`,
      deskripsi:
        "Transaksi dari impor ini ikut terhapus dari laporan. Berkas STS yang sama bisa diimpor ulang setelahnya.",
      label: "Hapus",
      bahaya: true,
    })
    if (!ya) return false
    try {
      await hapus.mutateAsync(daftar.map((i) => i.id))
      toast.success(daftar.length === 1 ? "Impor dihapus" : `${daftar.length} impor dihapus`)
      return true
    } catch (e) {
      toast.error("Impor gagal dihapus", { description: (e as Error).message })
      return false
    }
  }

  const kolom = [
    ki.accessor("tanggalAkhir", {
      header: ({ column }) => <HeaderUrut column={column} judul="Periode" />,
      cell: ({ row }) => (
        <button
          type="button"
          onClick={() => lihat(row.original)}
          className="grid min-w-48 gap-0.5 rounded-md text-left whitespace-normal focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
        >
          <span className="font-medium underline-offset-4 hover:underline">
            {labelPeriode(row.original.tanggalAwal, row.original.tanggalAkhir)}
          </span>
          <span className="text-xs break-all text-muted-foreground">{row.original.namaBerkas}</span>
        </button>
      ),
      enableHiding: false,
    }),
    ki.accessor("namaLoket", { header: "Loket" }),
    ki.accessor("jumlahTransaksi", {
      header: ({ column }) => <HeaderUrut column={column} judul="Transaksi" kanan />,
      cell: ({ row }) => (
        <div className="text-right tabular-nums">
          {angka(row.original.jumlahTransaksi)}
          {row.original.jumlahDilewati > 0 && (
            <span className="block text-xs text-muted-foreground">{angka(row.original.jumlahDilewati)} dilewati</span>
          )}
        </div>
      ),
    }),
    ki.accessor("total", {
      header: ({ column }) => <HeaderUrut column={column} judul="Total" kanan />,
      cell: ({ row }) => <div className="text-right tabular-nums">{rupiah(row.original.total)}</div>,
    }),
    ki.accessor("tampil", {
      header: "Portal",
      cell: ({ row }) =>
        administrator ? (
          <Switch
            checked={row.original.tampil}
            disabled={tampil.isPending}
            onCheckedChange={(nyala) => void ubahTampil(row.original, nyala)}
            aria-label={`Tampilkan ${judulImpor(row.original)} di portal`}
          />
        ) : (
          <NadaBadge nada={row.original.tampil ? "sukses" : "netral"}>{row.original.tampil ? "Tampil" : "Belum tampil"}</NadaBadge>
        ),
    }),
    ki.accessor("dibuat", {
      header: ({ column }) => <HeaderUrut column={column} judul="Diimpor" />,
      cell: ({ row }) => (
        <span className="grid text-sm whitespace-nowrap">
          <span>{tanggalJam(row.original.dibuat)}</span>
          <span className="text-xs text-muted-foreground">{row.original.oleh ?? "—"}</span>
        </span>
      ),
    }),
    ki.display({
      id: "aksi",
      cell: ({ row }) => (
        <MenuBaris label={judulImpor(row.original)} onHapus={() => void hapusImpor([row.original])}>
          <DropdownMenuItem onSelect={() => lihat(row.original)}>
            <IconCalendarEvent />
            Lihat laporannya
          </DropdownMenuItem>
        </MenuBaris>
      ),
    }),
  ]

  return (
    <section aria-labelledby="judul-riwayat-impor" className="flex min-w-0 flex-col gap-3">
      <div className="grid gap-1">
        <h2 id="judul-riwayat-impor" className="text-base font-heading font-(--fw-heading)">
          Riwayat impor
        </h2>
        <p className="max-w-2xl text-sm text-muted-foreground">
          Transaksi yang sudah pernah diimpor dilewati, jadi berkas bulanan aman diimpor setelah berkas hariannya. Menghapus
          impor menghapus transaksinya dari laporan. Impor baru tampil di portal setelah Administrator menyalakan kolom
          Portal.
        </p>
      </div>
      <DataTable
        data={data}
        memuat={memuat}
        columns={kolom}
        label="Riwayat impor berkas STS"
        sortingAwal={[{ id: "dibuat", desc: true }]}
        cari={{ placeholder: "Cari berkas atau loket…", teks: (i) => `${i.namaBerkas} ${i.namaLoket}` }}
        tindakanMassal={(baris, bersihkan) => (
          <Button variant="destructive" size="sm" onClick={async () => (await hapusImpor(baris)) && bersihkan()}>
            Hapus
          </Button>
        )}
        kosong={{ judul: "Belum ada berkas yang diimpor", deskripsi: "Impor berkas STS harian atau bulanan dari sistem BPAD." }}
      />
    </section>
  )
}

/* ---------- Impor ---------- */

const GRUP_LOKET: { jenis: JenisLoket; label: string }[] = [
  { jenis: "kantor", label: "Kantor" },
  { jenis: "keliling", label: "Samsat Keliling" },
  { jenis: "gerai", label: "Gerai tetap" },
  { jenis: "sementara", label: "Loket sementara" },
]

function SheetImpor({ tutup, selesai }: { tutup: () => void; selesai: (i: ImporPenerimaan) => void }) {
  const { data: daftarLoket } = useLoketPenerimaan()
  const pratinjau = usePratinjauImpor()
  const simpan = useSimpanImpor()
  const [loket, setLoket] = React.useState("kantor")
  const [berkas, setBerkas] = React.useState<File | null>(null)
  const [galat, setGalat] = React.useState<string | null>(null)
  const hasil = pratinjau.data

  const pilihBerkas = (f: File | null) => {
    pratinjau.reset()
    setGalat(null)
    if (f && !/\.(xls|html?)$/i.test(f.name)) {
      setGalat("Pilih berkas STS berformat .xls hasil ekspor sistem BPAD.")
      setBerkas(null)
      return
    }
    if (f && f.size > MAKS_BERKAS) {
      setGalat("Ukuran berkas maksimal 10 MB.")
      setBerkas(null)
      return
    }
    setBerkas(f)
  }

  const periksa = async () => {
    if (!berkas) {
      setGalat("Pilih berkas STS yang akan diimpor.")
      return
    }
    setGalat(null)
    try {
      await pratinjau.mutateAsync({ berkas, loket })
    } catch (e) {
      setGalat((e as Error).message)
    }
  }

  const kirim = async () => {
    if (!berkas) return
    setGalat(null)
    try {
      const i = await simpan.mutateAsync({ berkas, loket })
      const lengkap = hasil?.dilengkapi ? ` Nomoratur atau rincian ${angka(hasil.dilengkapi)} transaksi lama juga dilengkapi.` : ""
      if ("id" in i) {
        toast.success("Berkas STS diimpor", {
          description: `${angka(i.jumlahTransaksi)} transaksi, ${rupiah(i.total)}, untuk ${i.namaLoket}.${lengkap}`,
        })
        selesai(i)
      } else {
        // Berkas yang sudah pernah diimpor hanya melengkapi nomoratur (D-071) atau rincian 7 hari terakhir (D-079)
        toast.success("Transaksi dilengkapi", {
          description: `${angka(i.dilengkapi)} transaksi yang sudah diimpor kini tercatat nomoratur atau rinciannya.`,
        })
      }
      tutup()
    } catch (e) {
      setGalat((e as Error).message)
    }
  }

  return (
    <Sheet open onOpenChange={(o) => !o && tutup()}>
      <SheetContent className="flex w-full flex-col gap-0 p-0 sm:max-w-xl">
        <SheetHeader className="border-b pr-12">
          <SheetTitle>Impor berkas STS</SheetTitle>
          <SheetDescription>
            Berkas .xls hasil ekspor STS harian atau bulanan dari sistem BPAD. Nomor polisi, nama pemilik, RT/RW, dan kolom lain
            untuk Rincian penerimaan hanya disimpan (terenkripsi) untuk transaksi 7 hari terakhir.
          </SheetDescription>
        </SheetHeader>
        <div className="grid flex-1 content-start gap-5 overflow-y-auto p-4">
          <div className="grid gap-2">
            <Label htmlFor="impor-loket">Loket asal berkas</Label>
            <Select
              value={loket}
              onValueChange={(v) => {
                setLoket(v)
                pratinjau.reset()
              }}
            >
              <SelectTrigger id="impor-loket" className="w-full">
                <SelectValue placeholder="Pilih loket" />
              </SelectTrigger>
              <SelectContent>
                {GRUP_LOKET.map((g) => {
                  const isi = (daftarLoket ?? []).filter((l) => l.jenis === g.jenis)
                  return isi.length === 0 ? null : (
                    <SelectGroup key={g.jenis}>
                      <SelectLabel>{g.label}</SelectLabel>
                      {isi.map((l) => (
                        <SelectItem key={l.kunci} value={l.kunci}>
                          {l.nama}
                        </SelectItem>
                      ))}
                    </SelectGroup>
                  )
                })}
                {!daftarLoket && <SelectItem value="kantor">Loket SAMSAT</SelectItem>}
              </SelectContent>
            </Select>
            <p className="text-xs text-muted-foreground">
              Berkas STS tidak mencantumkan loketnya, jadi pilih loket yang mengekspor berkas ini.
            </p>
          </div>
          <div className="grid gap-2">
            <Label htmlFor="impor-berkas">Berkas STS</Label>
            <Input
              id="impor-berkas"
              type="file"
              accept=".xls,.htm,.html,application/vnd.ms-excel,text/html"
              onChange={(e) => pilihBerkas(e.target.files?.[0] ?? null)}
            />
            {berkas && (
              <p className="text-xs text-muted-foreground">
                {berkas.name} · {ukuranBerkas(berkas.size)}
              </p>
            )}
          </div>
          {galat && (
            <Alert variant="destructive">
              <IconAlertTriangle />
              <AlertTitle>Berkas belum bisa diimpor</AlertTitle>
              <AlertDescription>{galat}</AlertDescription>
            </Alert>
          )}
          {hasil && <HasilPratinjau hasil={hasil} />}
        </div>
        <SheetFooter className="flex-row justify-end gap-2 border-t">
          <Button type="button" variant="outline" onClick={tutup}>
            Batal
          </Button>
          {hasil ? (
            <Button type="button" onClick={kirim} disabled={simpan.isPending || (hasil.baru.transaksi === 0 && hasil.dilengkapi === 0)}>
              {simpan.isPending && <Spinner />}
              {hasil.baru.transaksi > 0 || hasil.dilengkapi === 0
                ? `Simpan ${angka(hasil.baru.transaksi)} transaksi`
                : `Lengkapi ${angka(hasil.dilengkapi)} transaksi`}
            </Button>
          ) : (
            <Button type="button" onClick={periksa} disabled={pratinjau.isPending || !berkas}>
              {pratinjau.isPending && <Spinner />}
              Periksa berkas
            </Button>
          )}
        </SheetFooter>
      </SheetContent>
    </Sheet>
  )
}

function HasilPratinjau({ hasil }: { hasil: PratinjauImpor }) {
  const { berkas, baru } = hasil
  return (
    <div className="grid gap-4 rounded-lg border p-4" aria-live="polite">
      <div className="grid gap-0.5">
        <p className="font-medium">
          {hasil.loket.nama}
          {berkas.tanggalAwal && berkas.tanggalAkhir && ` · ${labelPeriode(berkas.tanggalAwal, berkas.tanggalAkhir)}`}
        </p>
        <p className="text-xs break-all text-muted-foreground">{hasil.namaBerkas}</p>
      </div>
      <dl className="grid gap-3 text-sm sm:grid-cols-3">
        <div className="grid gap-0.5">
          <dt className="text-xs text-muted-foreground">Di berkas</dt>
          <dd className="font-medium tabular-nums">{angka(berkas.transaksi)} transaksi</dd>
          <dd className="text-xs text-muted-foreground tabular-nums">{rupiah(berkas.jumlah)}</dd>
        </div>
        <div className="grid gap-0.5">
          <dt className="text-xs text-muted-foreground">Akan disimpan</dt>
          <dd className="font-medium tabular-nums">{angka(baru.transaksi)} transaksi</dd>
          <dd className="text-xs text-muted-foreground tabular-nums">{rupiah(baru.jumlah)}</dd>
        </div>
        <div className="grid gap-0.5">
          <dt className="text-xs text-muted-foreground">Dilewati</dt>
          <dd className="font-medium tabular-nums">{angka(hasil.dilewati)} transaksi</dd>
          <dd className="text-xs text-muted-foreground">sudah pernah diimpor atau tercantum dua kali</dd>
        </div>
      </dl>
      {hasil.dilengkapi > 0 && (
        <Alert>
          <IconInfoCircle />
          <AlertDescription>
            {angka(hasil.dilengkapi)} transaksi yang dilewati diimpor sebelum nomoratur atau rinciannya dicatat. Yang kosong akan
            diisi dari berkas ini tanpa menghitung penerimaannya dua kali.
          </AlertDescription>
        </Alert>
      )}
      {baru.transaksi === 0 ? (
        hasil.dilengkapi === 0 && (
          <Alert>
            <IconInfoCircle />
            <AlertDescription>Semua transaksi di berkas ini sudah pernah diimpor, jadi tidak ada yang perlu disimpan.</AlertDescription>
          </Alert>
        )
      ) : (
        <div className="overflow-hidden rounded-md border">
          <Table aria-label="Transaksi yang akan disimpan per jenis daftar">
            <TableHeader className="bg-muted">
              <TableRow>
                <TableHead>Jenis daftar</TableHead>
                <TableHead className="text-right">Transaksi</TableHead>
                <TableHead className="text-right">Jumlah</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {baru.jenisDaftar.map((j) => (
                <TableRow key={j.nilai}>
                  <TableCell className="whitespace-normal">{labelNilai("jenisDaftar", j)}</TableCell>
                  <TableCell className="text-right tabular-nums">{angka(j.transaksi)}</TableCell>
                  <TableCell className="text-right tabular-nums">{rupiah(j.jumlah)}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </div>
      )}
    </div>
  )
}
