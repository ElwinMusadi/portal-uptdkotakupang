/*
 * Ringkasan, kartu per loket, dan grafik Laporan Penerimaan (D-047), dipakai
 * halaman Laporan Penerimaan di dashboard dan halaman Ikhtisar (D-099).
 */
import * as React from "react"
import { IconTrendingDown, IconTrendingUp } from "@tabler/icons-react"
import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from "recharts"

import { Badge } from "@/components/ui/badge"
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { ChartContainer, ChartTooltip, ChartTooltipContent, type ChartConfig } from "@/components/ui/chart"
import { angka, rupiah, tanggal } from "@/lib/format"
import {
  bagianOpsen,
  bagianProvinsi,
  KOMPONEN,
  labelNilai,
  perubahan,
  type BarisRincian,
  type Dimensi,
  type Jumlah,
  type LaporanPenerimaan,
} from "@/lib/penerimaan"
import { cn } from "@/lib/utils"

import { labelPeriode, TANGGAL_ISO } from "../../../portal/penerimaan/periode"

/**
 * Bagian laporan yang dipakai komponen di berkas ini. Ikhtisar tidak memuat
 * pilihan saringan dan sebagian rincian, jadi rinciannya boleh tidak lengkap.
 */
export type IntiLaporan = Pick<LaporanPenerimaan, "dari" | "sampai" | "ringkasan" | "pembanding" | "loketBuka"> & {
  rincian: Partial<Record<Dimensi | "tanggal", BarisRincian[]>>
}

/* ---------- Angka ---------- */

/** Porsi dalam persen; porsi yang ada tetapi di bawah 0,05% tidak dibulatkan menjadi "0,0%" */
export function persen(bagian: number, total: number): string {
  if (total <= 0) return "—"
  const p = (bagian / total) * 100
  return p > 0 && p < 0.05 ? "<0,1%" : `${angka(p, 1)}%`
}

/** Rupiah ringkas untuk sumbu grafik, mis. "563 jt" */
export function rupiahRingkas(n: number): string {
  if (n >= 1e9) return `${angka(n / 1e9, 1)} M`
  if (n >= 1e6) return `${angka(n / 1e6)} jt`
  if (n >= 1e3) return `${angka(n / 1e3)} rb`
  return angka(n)
}

export const potong = (teks: string, maks: number) => (teks.length > maks ? `${teks.slice(0, maks - 1)}…` : teks)

export const KONFIG_KOMPONEN = {
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

/* ---------- Ringkasan (bento) ---------- */

export function BadgePerubahan({ nilai }: { nilai: number | null }) {
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
export function AngkaBerlencana({ nilai, lencana, className }: { nilai: string; lencana?: React.ReactNode; className?: string }) {
  return (
    <div className="flex flex-wrap items-center gap-x-3 gap-y-1.5">
      <CardTitle className={cn("tabular-nums", className)}>{nilai}</CardTitle>
      {lencana}
    </div>
  )
}

export function KartuAngka({
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

export function Ringkasan({ laporan }: { laporan: IntiLaporan }) {
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
  const baru = (laporan.rincian.jenisDaftar ?? []).find((b) => b.nilai.toUpperCase() === "BARU")

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

export function PerLoket({ laporan, adaSaringan }: { laporan: IntiLaporan; adaSaringan: boolean }) {
  const baris = laporan.rincian.loket ?? []
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
const fmtBulanSumbu = new Intl.DateTimeFormat("id-ID", { month: "short", year: "2-digit", timeZone: "UTC" })
const fmtBulan = new Intl.DateTimeFormat("id-ID", { month: "long", year: "numeric", timeZone: "UTC" })

/**
 * Penerimaan per hari dari rincian tanggal. Bila perBulan, hari-hari itu
 * dijumlahkan per bulan (untuk periode panjang di Ikhtisar). Bila bukaHari
 * diisi, memilih batang membuka laporan hari itu.
 */
export function GrafikHarian({
  laporan,
  bukaHari,
  perBulan = false,
}: {
  laporan: IntiLaporan
  bukaHari?: (hari: string) => void
  perBulan?: boolean
}) {
  const data = React.useMemo(() => {
    const hari = (laporan.rincian.tanggal ?? []).map((b) => ({ tanggal: b.nilai, jumlah: b.jumlah, transaksi: b.transaksi }))
    if (!perBulan) return hari
    const bulan = new Map<string, { tanggal: string; jumlah: number; transaksi: number }>()
    for (const h of hari) {
      const kunci = `${h.tanggal.slice(0, 7)}-01`
      const isi = bulan.get(kunci) ?? { tanggal: kunci, jumlah: 0, transaksi: 0 }
      isi.jumlah += h.jumlah
      isi.transaksi += h.transaksi
      bulan.set(kunci, isi)
    }
    return [...bulan.values()]
  }, [laporan.rincian.tanggal, perBulan])
  const jumlahHari = laporan.rincian.tanggal?.length ?? 0

  return (
    <Card className="shadow-xs">
      <CardHeader>
        <CardTitle className="text-base">{perBulan ? "Penerimaan per bulan" : "Penerimaan per hari"}</CardTitle>
        <CardDescription>
          {perBulan ? `${angka(data.length)} bulan, ${angka(jumlahHari)} hari berisi transaksi.` : `${angka(data.length)} hari berisi transaksi.`}
          {bukaHari && " Pilih batang untuk membuka laporan harian."}
        </CardDescription>
      </CardHeader>
      <CardContent className="px-2 sm:px-6">
        <ChartContainer config={KONFIG_HARIAN} className="aspect-auto h-60 w-full">
          <BarChart
            data={data}
            margin={{ left: 4, right: 4 }}
            onClick={(state) => {
              const hari = state?.activeLabel
              if (bukaHari && typeof hari === "string" && TANGGAL_ISO.test(hari)) bukaHari(hari)
            }}
            className={cn(bukaHari && "cursor-pointer")}
          >
            <CartesianGrid vertical={false} />
            <XAxis
              dataKey="tanggal"
              tickLine={false}
              axisLine={false}
              tickMargin={8}
              minTickGap={16}
              tickFormatter={(v: string) => (perBulan ? fmtBulanSumbu : fmtTanggalSumbu).format(new Date(`${v}T00:00:00Z`))}
            />
            <YAxis tickLine={false} axisLine={false} width={56} tickFormatter={(v: number) => rupiahRingkas(v)} />
            <ChartTooltip
              cursor={false}
              content={
                <ChartTooltipContent
                  labelFormatter={(_, payload) => {
                    const titik = payload?.[0]?.payload as { tanggal: string; transaksi: number } | undefined
                    if (!titik) return ""
                    const waktu = perBulan ? fmtBulan.format(new Date(`${titik.tanggal}T00:00:00Z`)) : tanggal(titik.tanggal, "lengkap")
                    return `${waktu} · ${angka(titik.transaksi)} transaksi`
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

export function GrafikRincian({
  laporan,
  dimensi,
  judul,
  deskripsi,
  batas = 8,
}: {
  laporan: IntiLaporan
  dimensi: Dimensi
  judul: string
  deskripsi?: string
  batas?: number
}) {
  const baris = React.useMemo(() => laporan.rincian[dimensi] ?? [], [laporan.rincian, dimensi])
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
