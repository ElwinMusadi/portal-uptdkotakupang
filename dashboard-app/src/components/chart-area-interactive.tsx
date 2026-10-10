import * as React from "react"
import { Area, AreaChart, CartesianGrid, XAxis } from "recharts"

import { useIsMobile } from "@/hooks/use-mobile"
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import {
  ChartContainer,
  ChartTooltip,
  ChartTooltipContent,
  type ChartConfig,
} from "@/components/ui/chart"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { Skeleton } from "@/components/ui/skeleton"
import { ToggleGroup, ToggleGroupItem } from "@/components/ui/toggle-group"
import { angka, hariIniIso, ZONA_WAKTU } from "@/lib/format"
import { useKoleksi } from "@/lib/queries"

export const description = "Grafik area interaktif pesan masuk"

const chartConfig = {
  pesan: {
    label: "Pesan",
  },
  pengaduan: {
    label: "Pengaduan",
    color: "var(--chart-1)",
  },
  lainnya: {
    label: "Pertanyaan & masukan",
    color: "var(--chart-2)",
  },
} satisfies ChartConfig

/** `kelompok`: jumlah hari per titik grafik (3 bulan dijumlah per minggu agar tren terbaca) */
const RENTANG = {
  "90d": { hari: 91, kelompok: 7, label: "3 bulan terakhir" },
  "30d": { hari: 30, kelompok: 1, label: "30 hari terakhir" },
  "7d": { hari: 7, kelompok: 1, label: "7 hari terakhir" },
} as const

type Rentang = keyof typeof RENTANG

const fmtTanggal = (v: string) =>
  new Date(`${v}T12:00:00+08:00`).toLocaleDateString("id-ID", {
    month: "short",
    day: "numeric",
    timeZone: ZONA_WAKTU,
  })

export function ChartAreaInteractive() {
  const isMobile = useIsMobile()
  const [timeRange, setTimeRange] = React.useState<Rentang>("90d")
  const { data: pesan } = useKoleksi("pesan")

  React.useEffect(() => {
    if (isMobile) setTimeRange("7d")
  }, [isMobile])

  const { kelompok } = RENTANG[timeRange]
  const data = React.useMemo(() => {
    if (!pesan) return []
    const kini = Date.now()
    const jumlahTitik = RENTANG[timeRange].hari / kelompok
    const baris: { date: string; akhir: string; pengaduan: number; lainnya: number }[] = []
    // tanggal (WITA) → indeks titik grafik
    const titik = new Map<string, number>()
    for (let i = jumlahTitik - 1; i >= 0; i--) {
      const akhir = kini - i * kelompok * 86400000
      const mulai = akhir - (kelompok - 1) * 86400000
      for (let j = 0; j < kelompok; j++) titik.set(hariIniIso(new Date(mulai + j * 86400000)), baris.length)
      baris.push({ date: hariIniIso(new Date(mulai)), akhir: hariIniIso(new Date(akhir)), pengaduan: 0, lainnya: 0 })
    }
    for (const p of pesan) {
      const i = titik.get(hariIniIso(new Date(p.diterima)))
      if (i === undefined) continue
      if (p.jenis === "pengaduan") baris[i].pengaduan++
      else baris[i].lainnya++
    }
    return baris
  }, [pesan, timeRange, kelompok])

  const total = data.reduce((s, d) => s + d.pengaduan + d.lainnya, 0)
  const pengaduan = data.reduce((s, d) => s + d.pengaduan, 0)

  return (
    <Card className="@container/card">
      <CardHeader>
        <CardTitle>Pesan & pengaduan masuk</CardTitle>
        <CardDescription>
          <span className="hidden @[540px]/card:block">
            {angka(total)} pesan, {angka(pengaduan)} di antaranya pengaduan ·{" "}
            {RENTANG[timeRange].label}
            {kelompok > 1 && ", per minggu"}
          </span>
          <span className="@[540px]/card:hidden">{RENTANG[timeRange].label}</span>
        </CardDescription>
        <CardAction>
          <ToggleGroup
            type="single"
            value={timeRange}
            onValueChange={(v) => v && setTimeRange(v as Rentang)}
            variant="outline"
            className="hidden *:data-[slot=toggle-group-item]:px-4! @[767px]/card:flex"
          >
            <ToggleGroupItem value="90d">3 bulan</ToggleGroupItem>
            <ToggleGroupItem value="30d">30 hari</ToggleGroupItem>
            <ToggleGroupItem value="7d">7 hari</ToggleGroupItem>
          </ToggleGroup>
          <Select value={timeRange} onValueChange={(v) => setTimeRange(v as Rentang)}>
            <SelectTrigger
              className="flex w-40 **:data-[slot=select-value]:block **:data-[slot=select-value]:truncate @[767px]/card:hidden"
              size="sm"
              aria-label="Pilih rentang waktu"
            >
              <SelectValue placeholder="3 bulan terakhir" />
            </SelectTrigger>
            <SelectContent className="rounded-xl">
              {(Object.keys(RENTANG) as Rentang[]).map((k) => (
                <SelectItem key={k} value={k} className="rounded-lg">
                  {RENTANG[k].label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </CardAction>
      </CardHeader>
      <CardContent className="px-2 pt-4 sm:px-6 sm:pt-6">
        {!pesan ? (
          <Skeleton className="h-[250px] w-full" />
        ) : (
          <ChartContainer config={chartConfig} className="aspect-auto h-[250px] w-full">
            <AreaChart data={data} accessibilityLayer>
              <defs>
                <linearGradient id="fillPengaduan" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="5%" stopColor="var(--color-pengaduan)" stopOpacity={1.0} />
                  <stop offset="95%" stopColor="var(--color-pengaduan)" stopOpacity={0.1} />
                </linearGradient>
                <linearGradient id="fillLainnya" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="5%" stopColor="var(--color-lainnya)" stopOpacity={0.8} />
                  <stop offset="95%" stopColor="var(--color-lainnya)" stopOpacity={0.1} />
                </linearGradient>
              </defs>
              <CartesianGrid vertical={false} />
              <XAxis
                dataKey="date"
                tickLine={false}
                axisLine={false}
                tickMargin={8}
                minTickGap={32}
                tickFormatter={fmtTanggal}
              />
              <ChartTooltip
                cursor={false}
                content={
                  <ChartTooltipContent
                    labelFormatter={(v, payload) => {
                      const akhir = (payload?.[0]?.payload as { akhir?: string } | undefined)?.akhir
                      return kelompok > 1 && akhir
                        ? `${fmtTanggal(String(v))} – ${fmtTanggal(akhir)}`
                        : fmtTanggal(String(v))
                    }}
                    indicator="dot"
                  />
                }
              />
              <Area
                dataKey="lainnya"
                type="monotone"
                fill="url(#fillLainnya)"
                stroke="var(--color-lainnya)"
                stackId="a"
              />
              <Area
                dataKey="pengaduan"
                type="monotone"
                fill="url(#fillPengaduan)"
                stroke="var(--color-pengaduan)"
                stackId="a"
              />
            </AreaChart>
          </ChartContainer>
        )}
      </CardContent>
    </Card>
  )
}
