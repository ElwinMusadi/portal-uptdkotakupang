import * as React from "react"
import {
  IconAlertTriangle,
  IconChevronLeft,
  IconChevronRight,
  IconDownload,
  IconInfoCircle,
  IconPrinter,
  IconTargetArrow,
} from "@tabler/icons-react"
import { toast } from "sonner"

import { InputRupiah } from "@/components/form/input-rupiah"
import { Halaman, PengantarHalaman } from "@/components/data/page"
import { NavPenerimaan } from "@/components/penerimaan/nav-penerimaan"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from "@/components/ui/empty"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Separator } from "@/components/ui/separator"
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from "@/components/ui/sheet"
import { Skeleton } from "@/components/ui/skeleton"
import { Spinner } from "@/components/ui/spinner"
import { Switch } from "@/components/ui/switch"
import { ApiError } from "@/lib/api"
import { angka, hariIniIso, rupiah, tanggalJam } from "@/lib/format"
import {
  LABEL_BAGIAN,
  unduhRealisasi,
  useRealisasi,
  useSimpanTarget,
  type BagianRealisasi,
  type BarisRealisasi,
  type KunciTarget,
  type RealisasiPenerimaan,
  type SaldoAwal,
  type TargetPenerimaan,
} from "@/lib/penerimaan"
import { cn } from "@/lib/utils"

import { geserPeriode, labelTanggal, periodeBulan } from "../../../portal/penerimaan/periode"

/* ---------- Bulan ---------- */

const NAMA_BULAN = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"]

const kodeBulan = (tahun: number, bulan: number) => `${tahun}-${String(bulan).padStart(2, "0")}`
const labelBulan = (tahun: number, bulan: number) => `${NAMA_BULAN[bulan - 1]} ${tahun}`
const geserBulan = (bulan: string, n: number) => geserPeriode(periodeBulan(bulan), n).dari.slice(0, 7)

/** "Januari–September 2026", atau "Januari 2026" bila saldo awal hanya sebulan */
function rentangSaldo(tahun: number, sampaiBulan: number): string {
  return sampaiBulan === 1 ? labelBulan(tahun, 1) : `Januari–${labelBulan(tahun, sampaiBulan)}`
}

/* ---------- Angka Form I ---------- */

/** Rupiah tanpa "Rp" seperti di Form I; nol ditulis "-" */
const nilaiForm = (n: number | null) => (n === null ? "" : n === 0 ? "-" : angka(n))
const persenForm = (p: number | null) => (p === null ? "" : `${angka(p, 2)}%`)

/* ---------- Halaman ---------- */

export default function HalamanRealisasi() {
  const [bulan, setBulan] = React.useState(() => hariIniIso().slice(0, 7))
  const { data, isError, error, isFetching, refetch } = useRealisasi(bulan)
  const [ubahTarget, setUbahTarget] = React.useState(false)
  const [mengunduh, setMengunduh] = React.useState(false)
  const tabel = React.useRef<HTMLTableElement>(null)

  const tahun = Number(bulan.slice(0, 4))
  // Selama bulan lain dimuat, laporan sebelumnya tetap tampil redup
  const laporan = data
  const sesuai = !!data && kodeBulan(data.tahun, data.bulan) === bulan
  const siap = sesuai && data.tersedia
  // Target baru bisa diubah setelah target tahun itu termuat, agar isian kosong tidak menimpanya
  const targetTermuat = !!data && data.tahun === tahun

  const unduh = async () => {
    setMengunduh(true)
    try {
      await unduhRealisasi(bulan)
    } catch (e) {
      toast.error("Berkas Excel gagal diunduh", { description: (e as Error).message })
    } finally {
      setMengunduh(false)
    }
  }

  const cetak = () => {
    if (siap && tabel.current) cetakFormI(tabel.current, data)
  }

  return (
    <Halaman>
      <NavPenerimaan />
      <PengantarHalaman deskripsi="Realisasi PKB, BBNKB, dan dendanya dari berkas STS semua loket terhadap target tahunan, dalam susunan Form I. Opsen kabupaten/kota tidak termasuk.">
        <Button onClick={() => setUbahTarget(true)} disabled={!targetTermuat}>
          <IconTargetArrow />
          Target {tahun}
        </Button>
      </PengantarHalaman>

      <PilihBulan bulan={bulan} ubah={setBulan}>
        <Button variant="outline" onClick={cetak} disabled={!siap}>
          <IconPrinter />
          Cetak
        </Button>
        <Button variant="outline" onClick={unduh} disabled={!siap || mengunduh}>
          {mengunduh ? <Spinner /> : <IconDownload />}
          Unduh Excel
        </Button>
      </PilihBulan>

      {isError ? (
        <Alert variant="destructive">
          <IconAlertTriangle />
          <AlertTitle>Realisasi gagal dimuat</AlertTitle>
          <AlertDescription>
            <p>{(error as Error).message}</p>
            <Button variant="outline" size="sm" className="mt-2" onClick={() => void refetch()}>
              Coba lagi
            </Button>
          </AlertDescription>
        </Alert>
      ) : !laporan ? (
        <div className="grid gap-4 md:gap-6">
          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            {[0, 1, 2, 3].map((i) => (
              <Skeleton key={i} className="h-32 rounded-xl" />
            ))}
          </div>
          <Skeleton className="h-96 rounded-xl" />
        </div>
      ) : (
        <div className={cn("grid grid-cols-1 gap-4 transition-opacity md:gap-6", isFetching && "opacity-60")} aria-busy={isFetching}>
          {!laporan.target && (
            <Alert>
              <IconInfoCircle />
              <AlertTitle>Target {laporan.tahun} belum diisi</AlertTitle>
              <AlertDescription>
                <p>Realisasi tetap dihitung, tetapi kolom target dan persentase kosong sampai targetnya diisi.</p>
                <Button variant="outline" size="sm" className="mt-2" onClick={() => setUbahTarget(true)}>
                  Isi target {laporan.tahun}
                </Button>
              </AlertDescription>
            </Alert>
          )}
          {laporan.tersedia ? (
            <>
              <Ringkasan laporan={laporan} />
              <Card className="gap-4">
                <CardHeader>
                  <CardTitle className="text-base">Form I · {labelBulan(laporan.tahun, laporan.bulan)}</CardTitle>
                  <CardDescription>
                    Realisasi penerimaan gabungan semua loket, sama seperti berkas Excel dan hasil cetaknya.
                  </CardDescription>
                </CardHeader>
                <CardContent className="px-0 sm:px-6">
                  <TabelFormI laporan={laporan} ref={tabel} />
                </CardContent>
              </Card>
              <Sumber laporan={laporan} />
            </>
          ) : (
            <TertutupSaldo laporan={laporan} ubah={setBulan} />
          )}
        </div>
      )}

      {ubahTarget && targetTermuat && <SheetTarget tahun={tahun} target={data.target} tutup={() => setUbahTarget(false)} />}
    </Halaman>
  )
}

/** Bulan laporan dengan tombol bulan sebelumnya dan berikutnya, lalu aksi untuk Form I bulan itu */
function PilihBulan({ bulan, ubah, children }: { bulan: string; ubah: (b: string) => void; children: React.ReactNode }) {
  const [tahun, nomor] = bulan.split("-").map(Number)
  return (
    <Card className="gap-4 py-4">
      <CardContent className="flex flex-wrap items-center gap-x-4 gap-y-3 px-4">
        <div className="flex items-center gap-1">
          <Button variant="outline" size="icon" aria-label="Bulan sebelumnya" onClick={() => ubah(geserBulan(bulan, -1))}>
            <IconChevronLeft />
          </Button>
          <Input
            type="month"
            aria-label="Bulan laporan"
            value={bulan}
            min="2000-01"
            max="2099-12"
            onChange={(e) => e.target.value && ubah(e.target.value)}
            className="w-auto"
          />
          <Button
            variant="outline"
            size="icon"
            aria-label="Bulan berikutnya"
            disabled={bulan >= "2099-12"}
            onClick={() => ubah(geserBulan(bulan, 1))}
          >
            <IconChevronRight />
          </Button>
        </div>
        <p className="text-sm font-medium" aria-live="polite">
          {labelBulan(tahun, nomor)}
        </p>
        <div className="flex flex-wrap items-center gap-2 sm:ml-auto">{children}</div>
      </CardContent>
    </Card>
  )
}

/* ---------- Ringkasan ---------- */

function Ringkasan({ laporan }: { laporan: RealisasiPenerimaan }) {
  const total = laporan.baris.find((b) => b.jenis === "total")
  const [pajak, denda] = laporan.baris.filter((b) => b.jenis === "jumlah")
  if (!total || !pajak || !denda) return null
  const sampaiBulan = `s.d. ${labelBulan(laporan.tahun, laporan.bulan)}`
  return (
    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <KartuCapaian judul={`Total realisasi ${sampaiBulan}`} baris={total} />
      <KartuCapaian judul="Pajak (PKB dan BBNKB)" baris={pajak} />
      <KartuCapaian judul="Denda pajak" baris={denda} />
      <Card className="@container/card gap-3">
        <CardHeader>
          <CardDescription>Realisasi {labelBulan(laporan.tahun, laporan.bulan)}</CardDescription>
          <CardTitle className="text-xl tabular-nums @[250px]/card:text-2xl">{rupiah(total.ini)}</CardTitle>
        </CardHeader>
        <CardFooter className="text-sm text-muted-foreground">
          {laporan.loket.length === 0
            ? "Belum ada berkas STS bulan ini."
            : `${angka(laporan.loket.length)} loket, transaksi sampai ${labelTanggal(laporan.sampaiTanggal!)}.`}
        </CardFooter>
      </Card>
    </div>
  )
}

function KartuCapaian({ judul, baris }: { judul: string; baris: BarisRealisasi }) {
  const persen = baris.persen
  return (
    <Card className="@container/card gap-3">
      <CardHeader>
        <CardDescription>{judul}</CardDescription>
        <div className="flex flex-wrap items-center gap-x-3 gap-y-1.5">
          <CardTitle className="text-xl tabular-nums @[250px]/card:text-2xl">{rupiah(baris.sampaiIni)}</CardTitle>
          {persen !== null && <Badge variant="outline">{angka(persen, 2)}% dari target</Badge>}
        </div>
      </CardHeader>
      <CardFooter className="grid gap-2 text-sm text-muted-foreground">
        {persen !== null && (
          <div className="h-2 overflow-hidden rounded-full bg-muted" aria-hidden="true">
            <div className="h-full rounded-full bg-primary" style={{ width: `${Math.min(persen, 100)}%` }} />
          </div>
        )}
        <span>{baris.target ? `Target ${rupiah(baris.target)}` : "Target belum diisi"}</span>
      </CardFooter>
    </Card>
  )
}

/* ---------- Form I ---------- */

function TabelFormI({ laporan, ref }: { laporan: RealisasiPenerimaan; ref: React.Ref<HTMLTableElement> }) {
  const sel = "border border-border px-2 py-1.5"
  return (
    <div className="overflow-x-auto">
      <table ref={ref} className="w-full min-w-[50rem] border-collapse text-sm tabular-nums">
        <caption className="sr-only">
          Realisasi penerimaan gabungan {labelBulan(laporan.tahun, laporan.bulan)} terhadap target
        </caption>
        <thead className="bg-muted/50 text-xs font-semibold">
          <tr>
            <th rowSpan={2} scope="col" className={cn(sel, "w-12")}>
              NO.
            </th>
            <th rowSpan={2} scope="col" className={sel}>
              JENIS PENERIMAAN
            </th>
            <th rowSpan={2} scope="col" className={sel}>
              TARGET (Rp)
            </th>
            <th colSpan={3} scope="colgroup" className={sel}>
              REALISASI PENERIMAAN GABUNGAN
            </th>
            <th rowSpan={2} scope="col" className={cn(sel, "w-20")}>
              PCT
            </th>
          </tr>
          <tr>
            <th scope="col" className={sel}>
              S/D BULAN LALU (Rp)
            </th>
            <th scope="col" className={sel}>
              BULAN INI (Rp)
            </th>
            <th scope="col" className={sel}>
              S/D BULAN INI (Rp)
            </th>
          </tr>
          <tr data-nomor-kolom="">
            {[1, 2, 3, 4, 5, 6, 7].map((n) => (
              <th key={n} scope="col" className={cn(sel, "py-0.5 font-normal text-muted-foreground")}>
                {n}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {laporan.baris.map((b, i) => {
            const tebal = b.jenis === "jumlah" || b.jenis === "total"
            return (
              <tr
                key={i}
                data-jenis={b.jenis}
                className={cn(tebal && "font-semibold", b.jenis === "jumlah" && "bg-muted/30", b.jenis === "total" && "bg-muted/60")}
              >
                {tebal ? (
                  <th scope="row" colSpan={2} data-kolom="uraian" className={cn(sel, "text-center", b.jenis === "total" && "tracking-[0.3em]")}>
                    {b.uraian}
                  </th>
                ) : (
                  <>
                    {b.baris > 0 && (
                      <td rowSpan={b.baris} data-kolom="no" className={cn(sel, "text-center")}>
                        {b.no}
                      </td>
                    )}
                    <th scope="row" data-kolom="uraian" className={cn(sel, "text-left font-normal", b.jenis === "subjumlah" ? "pl-8" : "pl-3")}>
                      {b.uraian}
                    </th>
                  </>
                )}
                {b.baris > 0 && (
                  <td rowSpan={b.baris} className={cn(sel, "text-right whitespace-nowrap")}>
                    {laporan.target ? nilaiForm(b.target) : ""}
                  </td>
                )}
                <td className={cn(sel, "text-right whitespace-nowrap")}>{nilaiForm(b.lalu)}</td>
                <td className={cn(sel, "text-right whitespace-nowrap")}>{nilaiForm(b.ini)}</td>
                <td className={cn(sel, "text-right whitespace-nowrap")}>{nilaiForm(b.sampaiIni)}</td>
                <td className={cn(sel, "text-right whitespace-nowrap")}>{persenForm(b.persen)}</td>
              </tr>
            )
          })}
        </tbody>
      </table>
    </div>
  )
}

/** Sumber angka: saldo awal, bulan-bulan STS sebelumnya, dan loket bulan ini */
function Sumber({ laporan }: { laporan: RealisasiPenerimaan }) {
  const saldo = laporan.target?.saldoAwal
  const bulanStsPertama = (saldo?.sampaiBulan ?? 0) + 1
  const adaStsSebelumnya = laporan.bulan > bulanStsPertama
  const stsSebelumnya =
    bulanStsPertama === laporan.bulan - 1
      ? labelBulan(laporan.tahun, bulanStsPertama)
      : `${NAMA_BULAN[bulanStsPertama - 1]}–${labelBulan(laporan.tahun, laporan.bulan - 1)}`

  return (
    <Card className="gap-4">
      <CardHeader>
        <CardTitle className="text-base">Sumber angka</CardTitle>
        <CardDescription>
          {saldo
            ? `S/D bulan lalu memuat saldo awal ${rentangSaldo(laporan.tahun, saldo.sampaiBulan)} yang diisi manual${adaStsSebelumnya ? `, ditambah transaksi STS ${stsSebelumnya}` : ""}.`
            : laporan.bulan > 1
              ? `S/D bulan lalu dihitung dari transaksi STS ${stsSebelumnya}.`
              : "Januari tidak memiliki bulan lalu."}{" "}
          Bulan ini dihitung dari transaksi STS semua loket.
        </CardDescription>
      </CardHeader>
      <CardContent>
        {laporan.loket.length === 0 ? (
          <p className="text-sm text-muted-foreground">Belum ada berkas STS yang diimpor untuk {labelBulan(laporan.tahun, laporan.bulan)}.</p>
        ) : (
          <ul className="grid gap-2 sm:grid-cols-2 xl:grid-cols-3" role="list">
            {laporan.loket.map((l) => (
              <li key={l.nilai} className="grid gap-0.5 rounded-lg border px-3 py-2 text-sm">
                <span className="font-medium">{l.nama}</span>
                <span className="text-muted-foreground tabular-nums">
                  {angka(l.transaksi)} transaksi · {rupiah(l.jumlah)} · sampai {labelTanggal(l.sampaiTanggal)}
                </span>
              </li>
            ))}
          </ul>
        )}
      </CardContent>
    </Card>
  )
}

function TertutupSaldo({ laporan, ubah }: { laporan: RealisasiPenerimaan; ubah: (b: string) => void }) {
  const sampai = laporan.target?.saldoAwal?.sampaiBulan ?? 0
  const pertama = sampai + 1
  return (
    <Card>
      <CardContent>
        <Empty>
          <EmptyHeader>
            <EmptyMedia variant="icon">
              <IconTargetArrow />
            </EmptyMedia>
            <EmptyTitle>Bulan ini tercatat dalam saldo awal</EmptyTitle>
            <EmptyDescription>
              Realisasi {rentangSaldo(laporan.tahun, sampai)} diisi manual sebagai saldo awal, jadi tidak ada Form I per bulannya.
              {pertama <= 12 && ` Laporan per bulan tersedia mulai ${labelBulan(laporan.tahun, pertama)}.`}
            </EmptyDescription>
          </EmptyHeader>
          {pertama <= 12 && (
            <EmptyContent>
              <Button variant="outline" onClick={() => ubah(kodeBulan(laporan.tahun, pertama))}>
                Lihat {labelBulan(laporan.tahun, pertama)}
              </Button>
            </EmptyContent>
          )}
        </Empty>
      </CardContent>
    </Card>
  )
}

/* ---------- Cetak ---------- */

const JUDUL_FORM = [
  "REALISASI PENERIMAAN PAJAK DAN PENERIMAAN LAIN-LAIN YANG SAH",
  "UPTD. PENDAPATAN DAERAH WILAYAH KOTA KUPANG PADA",
  "BADAN PENDAPATAN DAN ASET DAERAH PROVINSI NUSA TENGGARA TIMUR",
]

const GAYA_CETAK = `
@page { size: A4 landscape; margin: 12mm; }
* { box-sizing: border-box; }
body { margin: 0; color: #000; font: 10pt Calibri, Carlito, Arial, sans-serif; }
.judul { margin: 0 0 14pt; text-align: center; font-weight: 700; line-height: 1.4; }
.atas { display: flex; justify-content: space-between; font-weight: 700; margin-bottom: 2pt; }
table { width: 100%; border-collapse: collapse; font-variant-numeric: tabular-nums; }
caption { display: none; }
th, td { border: 0.75pt solid #000; padding: 2pt 5pt; }
thead th { text-align: center; font-weight: 700; }
td { text-align: right; white-space: nowrap; }
td[data-kolom="no"] { text-align: center; }
tbody th { text-align: left; font-weight: 400; padding-left: 8pt; }
tr[data-jenis="subjumlah"] th { padding-left: 24pt; }
tr[data-jenis="jumlah"] th, tr[data-jenis="total"] th { text-align: center; font-weight: 700; padding-left: 5pt; }
tr[data-jenis="jumlah"] td, tr[data-jenis="total"] td { font-weight: 700; }
tr[data-jenis="total"] th { letter-spacing: 0.3em; }
`

/** Cetak Form I lewat bingkai tersembunyi: judul resmi di atas tabel yang sama dengan di layar, A4 mendatar */
function cetakFormI(tabel: HTMLTableElement, laporan: RealisasiPenerimaan) {
  const judul = [...JUDUL_FORM, `BULAN ${NAMA_BULAN[laporan.bulan - 1].toUpperCase()}`, `TAHUN ${laporan.tahun}`]
  const bingkai = document.createElement("iframe")
  bingkai.setAttribute("aria-hidden", "true")
  bingkai.tabIndex = -1
  bingkai.style.cssText = "position:fixed;right:0;bottom:0;width:0;height:0;border:0"
  bingkai.srcdoc = `<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Realisasi Penerimaan ${kodeBulan(laporan.tahun, laporan.bulan)}</title><style>${GAYA_CETAK}</style></head><body><div class="judul">${judul.join("<br>")}</div><div class="atas"><span>GABUNGAN</span><span>Form. I</span></div>${tabel.outerHTML}</body></html>`
  bingkai.onload = () => {
    const jendela = bingkai.contentWindow
    if (!jendela) return
    jendela.addEventListener("afterprint", () => bingkai.remove())
    jendela.focus()
    jendela.print()
  }
  document.body.append(bingkai)
}

/* ---------- Target dan saldo awal ---------- */

const ISIAN_TARGET: { kunci: KunciTarget; label: string; keterangan?: string }[] = [
  { kunci: "pkb", label: "PKB", keterangan: "Termasuk PKB tunggakan." },
  { kunci: "bbn", label: "BBNKB" },
  { kunci: "dendaPkb", label: "Denda PKB", keterangan: "Termasuk denda PKB tunggakan." },
  { kunci: "dendaBbn", label: "Denda BBNKB" },
]

const BAGIAN: BagianRealisasi[] = ["pkb", "tunggakan", "bbn", "dendaPkb", "dendaTunggakan", "dendaBbn"]

const SALDO_KOSONG: SaldoAwal = { sampaiBulan: 9, pkb: 0, tunggakan: 0, bbn: 0, dendaPkb: 0, dendaTunggakan: 0, dendaBbn: 0 }

function SheetTarget({ tahun, target, tutup }: { tahun: number; target: TargetPenerimaan | null; tutup: () => void }) {
  const simpan = useSimpanTarget()
  const [isian, setIsian] = React.useState<Record<KunciTarget, number>>({
    pkb: target?.pkb ?? 0,
    bbn: target?.bbn ?? 0,
    dendaPkb: target?.dendaPkb ?? 0,
    dendaBbn: target?.dendaBbn ?? 0,
  })
  const [adaSaldo, setAdaSaldo] = React.useState(!!target?.saldoAwal)
  const [saldo, setSaldo] = React.useState<SaldoAwal>(target?.saldoAwal ?? SALDO_KOSONG)
  const [galat, setGalat] = React.useState<{ pesan: string; isian: Record<string, string> } | null>(null)
  const id = React.useId()

  const pajak = isian.pkb + isian.bbn
  const denda = isian.dendaPkb + isian.dendaBbn

  const kirim = async (e: React.FormEvent) => {
    e.preventDefault()
    setGalat(null)
    try {
      await simpan.mutateAsync({ tahun, isian: { target: isian, saldoAwal: adaSaldo ? saldo : null } })
      toast.success(`Target ${tahun} disimpan`)
      tutup()
    } catch (err) {
      setGalat({ pesan: (err as Error).message, isian: err instanceof ApiError ? err.isian : {} })
    }
  }

  const pesanIsian = (nama: string) =>
    galat?.isian[nama] ? (
      <p id={`${id}-${nama}-galat`} className="text-xs text-destructive">
        {galat.isian[nama]}
      </p>
    ) : null

  return (
    <Sheet open onOpenChange={(o) => !o && tutup()}>
      <SheetContent className="flex w-full flex-col gap-0 p-0 sm:max-w-xl">
        <form onSubmit={kirim} className="flex min-h-0 flex-1 flex-col" noValidate>
          <SheetHeader className="border-b pr-12">
            <SheetTitle>Target penerimaan {tahun}</SheetTitle>
            <SheetDescription>
              Target tahunan Form I. Laporan setiap bulan tahun {tahun} memakai target terakhir yang disimpan.
            </SheetDescription>
          </SheetHeader>
          <div className="grid flex-1 content-start gap-6 overflow-y-auto p-4">
            <fieldset className="grid gap-4">
              <legend className="mb-3 text-sm font-semibold">Target</legend>
              {ISIAN_TARGET.map(({ kunci, label, keterangan }) => {
                const nama = `target.${kunci}`
                return (
                  <div key={kunci} className="grid gap-2">
                    <Label htmlFor={`${id}-${kunci}`}>{label}</Label>
                    <InputRupiah
                      id={`${id}-${kunci}`}
                      nilai={isian[kunci]}
                      ubah={(n) => setIsian((s) => ({ ...s, [kunci]: n }))}
                      invalid={!!galat?.isian[nama]}
                      describedBy={
                        [keterangan && `${id}-${kunci}-ket`, galat?.isian[nama] && `${id}-${nama}-galat`].filter(Boolean).join(" ") || undefined
                      }
                    />
                    {keterangan && (
                      <p id={`${id}-${kunci}-ket`} className="text-xs text-muted-foreground">
                        {keterangan}
                      </p>
                    )}
                    {pesanIsian(nama)}
                  </div>
                )
              })}
              <dl className="grid gap-1 rounded-lg bg-muted/50 px-3 py-2 text-sm tabular-nums">
                <div className="flex justify-between gap-4">
                  <dt className="text-muted-foreground">Jumlah pajak</dt>
                  <dd>{rupiah(pajak)}</dd>
                </div>
                <div className="flex justify-between gap-4">
                  <dt className="text-muted-foreground">Jumlah denda pajak</dt>
                  <dd>{rupiah(denda)}</dd>
                </div>
                <div className="flex justify-between gap-4 font-semibold">
                  <dt>Total</dt>
                  <dd>{rupiah(pajak + denda)}</dd>
                </div>
              </dl>
            </fieldset>

            <Separator />

            <fieldset className="grid gap-4">
              <legend className="sr-only">Saldo awal</legend>
              <div className="flex items-start justify-between gap-4">
                <div className="grid gap-1">
                  <Label htmlFor={`${id}-ada-saldo`}>Saldo awal</Label>
                  <p id={`${id}-ada-saldo-ket`} className="text-xs text-muted-foreground">
                    Untuk bulan-bulan sebelum berkas STS semua loket diimpor: isi realisasinya dari laporan sebelumnya. Transaksi
                    STS pada bulan-bulan itu tidak dihitung.
                  </p>
                </div>
                <Switch
                  id={`${id}-ada-saldo`}
                  checked={adaSaldo}
                  onCheckedChange={setAdaSaldo}
                  aria-describedby={`${id}-ada-saldo-ket`}
                />
              </div>
              {adaSaldo && (
                <>
                  <div className="grid gap-2">
                    <Label htmlFor={`${id}-sampai`}>Realisasi Januari sampai</Label>
                    <Select value={String(saldo.sampaiBulan)} onValueChange={(v) => setSaldo((s) => ({ ...s, sampaiBulan: Number(v) }))}>
                      <SelectTrigger id={`${id}-sampai`} className="w-full" aria-invalid={!!galat?.isian["saldoAwal.sampaiBulan"]}>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        {NAMA_BULAN.map((nama, i) => (
                          <SelectItem key={nama} value={String(i + 1)}>
                            {nama} {tahun}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                    <p className="text-xs text-muted-foreground">
                      Form I per bulan tersedia mulai{" "}
                      {saldo.sampaiBulan < 12 ? labelBulan(tahun, saldo.sampaiBulan + 1) : `tahun ${tahun + 1}`}.
                    </p>
                    {pesanIsian("saldoAwal.sampaiBulan")}
                  </div>
                  {BAGIAN.map((b) => {
                    const nama = `saldoAwal.${b}`
                    return (
                      <div key={b} className="grid gap-2">
                        <Label htmlFor={`${id}-saldo-${b}`}>{LABEL_BAGIAN[b]}</Label>
                        <InputRupiah
                          id={`${id}-saldo-${b}`}
                          nilai={saldo[b]}
                          ubah={(n) => setSaldo((s) => ({ ...s, [b]: n }))}
                          invalid={!!galat?.isian[nama]}
                          describedBy={galat?.isian[nama] ? `${id}-${nama}-galat` : undefined}
                        />
                        {pesanIsian(nama)}
                      </div>
                    )
                  })}
                </>
              )}
            </fieldset>

            {galat && (
              <Alert variant="destructive">
                <IconAlertTriangle />
                <AlertTitle>Target belum disimpan</AlertTitle>
                <AlertDescription>{galat.pesan}</AlertDescription>
              </Alert>
            )}
            {target?.diperbarui && (
              <p className="text-xs text-muted-foreground">
                Terakhir disimpan {tanggalJam(target.diperbarui)}
                {target.oleh ? ` oleh ${target.oleh}` : ""}.
              </p>
            )}
          </div>
          <SheetFooter className="flex-row justify-end gap-2 border-t">
            <Button type="button" variant="outline" onClick={tutup}>
              Batal
            </Button>
            <Button type="submit" disabled={simpan.isPending}>
              {simpan.isPending && <Spinner />}
              Simpan
            </Button>
          </SheetFooter>
        </form>
      </SheetContent>
    </Sheet>
  )
}
