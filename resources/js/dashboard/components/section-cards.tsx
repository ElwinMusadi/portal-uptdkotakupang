import * as React from "react"
import {
  IconAlertTriangle,
  IconClockHour4,
  IconTrendingDown,
  IconTrendingUp,
} from "@tabler/icons-react"
import { Link } from "react-router"

import { Badge } from "@/components/ui/badge"
import {
  Card,
  CardAction,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { Skeleton } from "@/components/ui/skeleton"
import { angka, selisihHari } from "@/lib/format"
import { sejakStatus, useItemAlur, usePemutakhiran } from "@/lib/hitung"
import { useKoleksi } from "@/lib/queries"

const HARI = 86400000

export function SectionCards() {
  const { data: alur } = useItemAlur()
  const { data: pesan } = useKoleksi("pesan")
  const pantau = usePemutakhiran()

  const m = React.useMemo(() => {
    if (!alur || !pesan || !pantau) return null
    const kini = Date.now()
    const menunggu = alur.filter((x) => x.status === "persetujuan")
    const lama = menunggu.filter((x) => selisihHari(sejakStatus(x)) > 2).length
    const terbitDalam = (dari: number, ke: number) =>
      alur.flatMap((x) => x.riwayat).filter((r) => {
        if (r.ke !== "terbit") return false
        const t = Date.parse(r.waktu)
        return t > kini - dari * HARI && t <= kini - ke * HARI
      }).length
    const terbit30 = terbitDalam(30, 0)
    const terbitSebelum = terbitDalam(60, 30)
    const perubahan = terbitSebelum ? ((terbit30 - terbitSebelum) / terbitSebelum) * 100 : null

    const terbuka = pesan.filter((p) => p.status !== "selesai")
    const baru = pesan.filter((p) => p.status === "baru").length
    const dijawab = pesan.filter((p) => p.tanggapan.length > 0)
    const rataJam = dijawab.length
      ? dijawab.reduce(
          (s, p) => s + (Date.parse(p.tanggapan[p.tanggapan.length - 1].waktu) - Date.parse(p.diterima)),
          0
        ) /
        dijawab.length /
        3600000
      : null

    const jatuhTempo = pantau.filter((p) => p.status === "jatuh-tempo")
    const segera = pantau.filter((p) => p.status === "segera").length

    return {
      menunggu: menunggu.length,
      lama,
      diperiksa: alur.filter((x) => x.status === "diperiksa").length,
      draf: alur.filter((x) => x.status === "draf" || x.status === "dikembalikan").length,
      terbit30,
      perubahan,
      terbuka: terbuka.length,
      baru,
      rataJam,
      jatuhTempo: jatuhTempo.length,
      jatuhTempoNama: jatuhTempo.map((p) => p.konten),
      segera,
    }
  }, [alur, pesan, pantau])

  if (!m) {
    return (
      <div className="grid grid-cols-1 gap-4 px-4 lg:px-6 @xl/main:grid-cols-2 @5xl/main:grid-cols-4">
        {Array.from({ length: 4 }).map((_, i) => (
          <Skeleton key={i} className="h-[178px] rounded-xl" />
        ))}
      </div>
    )
  }

  const naik = (m.perubahan ?? 0) >= 0

  return (
    <div className="grid grid-cols-1 gap-4 px-4 *:data-[slot=card]:bg-gradient-to-t *:data-[slot=card]:from-primary/5 *:data-[slot=card]:to-card *:data-[slot=card]:shadow-xs lg:px-6 @xl/main:grid-cols-2 @5xl/main:grid-cols-4 dark:*:data-[slot=card]:bg-card">
      <Card className="@container/card">
        <CardHeader>
          <CardDescription>Menunggu persetujuan</CardDescription>
          <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
            <Link to="/alur" className="hover:underline">
              {angka(m.menunggu)}
              <span className="sr-only"> konten menunggu persetujuan, buka alur publikasi</span>
            </Link>
          </CardTitle>
          <CardAction>
            <Badge variant="outline">
              <IconClockHour4 />
              {m.lama > 0 ? `${m.lama} lewat 2 hari` : "Tepat waktu"}
            </Badge>
          </CardAction>
        </CardHeader>
        <CardFooter className="flex-col items-start gap-1.5 text-sm">
          <div className="line-clamp-1 flex gap-2 font-medium">Perlu izin terbit atasan langsung</div>
          <div className="text-muted-foreground">
            {m.diperiksa} diperiksa · {m.draf} draf atau revisi
          </div>
        </CardFooter>
      </Card>

      <Card className="@container/card">
        <CardHeader>
          <CardDescription>Terbit 30 hari terakhir</CardDescription>
          <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
            {angka(m.terbit30)}
          </CardTitle>
          <CardAction>
            <Badge variant="outline">
              {naik ? <IconTrendingUp /> : <IconTrendingDown />}
              {m.perubahan === null ? `+${angka(m.terbit30)}` : `${naik ? "+" : ""}${angka(m.perubahan, 0)}%`}
            </Badge>
          </CardAction>
        </CardHeader>
        <CardFooter className="flex-col items-start gap-1.5 text-sm">
          <div className="line-clamp-1 flex gap-2 font-medium">
            {naik ? "Lebih aktif dari bulan lalu" : "Lebih sepi dari bulan lalu"}
            {naik ? <IconTrendingUp className="size-4" /> : <IconTrendingDown className="size-4" />}
          </div>
          <div className="text-muted-foreground">Berita, unduhan, dokumentasi, persyaratan</div>
        </CardFooter>
      </Card>

      <Card className="@container/card">
        <CardHeader>
          <CardDescription>Pesan belum selesai</CardDescription>
          <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
            <Link to="/pesan" className="hover:underline">
              {angka(m.terbuka)}
              <span className="sr-only"> pesan belum selesai, buka kotak pesan</span>
            </Link>
          </CardTitle>
          <CardAction>
            <Badge variant="outline">{m.baru} baru</Badge>
          </CardAction>
        </CardHeader>
        <CardFooter className="flex-col items-start gap-1.5 text-sm">
          <div className="line-clamp-1 flex gap-2 font-medium">Target balasan 3 hari kerja</div>
          <div className="text-muted-foreground">
            {m.rataJam === null
              ? "Belum ada pesan yang dijawab"
              : `Rata-rata dijawab dalam ${angka(m.rataJam, m.rataJam < 10 ? 1 : 0)} jam`}
          </div>
        </CardFooter>
      </Card>

      <Card className="@container/card">
        <CardHeader>
          <CardDescription>Jatuh tempo pemutakhiran</CardDescription>
          <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
            <Link to="/pemutakhiran" className="hover:underline">
              {angka(m.jatuhTempo)}
              <span className="sr-only"> konten jatuh tempo, buka jadwal pemutakhiran</span>
            </Link>
          </CardTitle>
          <CardAction>
            <Badge variant="outline">
              {m.jatuhTempo > 0 && <IconAlertTriangle />}
              {m.segera} segera
            </Badge>
          </CardAction>
        </CardHeader>
        <CardFooter className="flex-col items-start gap-1.5 text-sm">
          <div className="line-clamp-1 flex gap-2 font-medium">
            {m.jatuhTempo ? m.jatuhTempoNama.join(", ") : "Semua konten terkini"}
          </div>
          <div className="text-muted-foreground">Sesuai standar frekuensi SOP</div>
        </CardFooter>
      </Card>
    </div>
  )
}
