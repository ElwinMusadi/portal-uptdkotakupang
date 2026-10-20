import * as React from "react"
import { IconAlertTriangle, IconChevronLeft, IconChevronRight, IconDownload, IconInfoCircle, IconNumbers } from "@tabler/icons-react"

import { Halaman, PengantarHalaman } from "@/components/data/page"
import { NavPenerimaan } from "@/components/penerimaan/nav-penerimaan"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardAction, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from "@/components/ui/empty"
import { Input } from "@/components/ui/input"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Skeleton } from "@/components/ui/skeleton"
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { ToggleGroup, ToggleGroupItem } from "@/components/ui/toggle-group"
import { angka, hariIniIso } from "@/lib/format"
import {
  csvNomoratur,
  labelRentang,
  unduhTeks,
  useDaftarImpor,
  useLoketPenerimaan,
  useNomoratur,
  type HariNomoratur,
  type LaporanNomoratur,
  type LoketNomoratur,
} from "@/lib/penerimaan"
import { cn } from "@/lib/utils"

import { gantiMode, geserPeriode, labelPeriode, labelTanggal, periodeBulan, type Periode } from "../../../portal/penerimaan/periode"

const SEMUA = "semua"

/** Rentang yang ditampilkan per hari di laporan bulanan sebelum diringkas */
const RENTANG_RINGKAS = 3

/* ---------- Halaman ---------- */

export default function HalamanNomoratur() {
  const { data: daftarImpor } = useDaftarImpor()
  const { data: daftarLoket } = useLoketPenerimaan()
  const [periodePilihan, setPeriode] = React.useState<Periode | null>(null)
  const [loket, setLoket] = React.useState(SEMUA)
  const panel = React.useRef<HTMLDivElement>(null)

  // Sebelum pengguna memilih, laporan menampilkan hari terakhir yang sudah diimpor
  const terakhir = React.useMemo(
    () => (daftarImpor ? daftarImpor.reduce((m, i) => (i.tanggalAkhir > m ? i.tanggalAkhir : m), "") || hariIniIso() : null),
    [daftarImpor]
  )
  const periode: Periode | null = periodePilihan ?? (terakhir ? { mode: "harian", dari: terakhir, sampai: terakhir } : null)
  const { data, isError, error, isFetching, refetch } = useNomoratur(periode?.dari, periode?.sampai, loket === SEMUA ? "" : loket)

  const bukaHari = (tanggal: string) => {
    setPeriode({ mode: "harian", dari: tanggal, sampai: tanggal })
    panel.current?.scrollIntoView({ behavior: "smooth", block: "start" })
  }

  const unduh = () => {
    if (!data) return
    const nama = loket === SEMUA ? "semua-loket" : loket.replace(/[^a-z0-9-]+/gi, "-")
    const waktu = data.dari === data.sampai ? data.dari : data.dari.slice(0, 7)
    unduhTeks(csvNomoratur(data), `nomoratur-${nama}-${waktu}.csv`)
  }

  const kosong = !!data && data.ringkasan.transaksi + data.ringkasan.tanpaNomoratur + data.ringkasan.belumTercatat === 0

  return (
    <Halaman>
      <NavPenerimaan />
      <PengantarHalaman deskripsi="Pemakaian nomoratur SKPD per loket dari berkas STS: rentang nomor terpakai, nomor yang terlewat, dan nomor yang dipakai lebih dari sekali.">
        <Button variant="outline" onClick={unduh} disabled={!data || data.ringkasan.transaksi === 0}>
          <IconDownload />
          Unduh CSV
        </Button>
      </PengantarHalaman>

      <div ref={panel} className="scroll-mt-20">
        {periode ? (
          <PanelPeriode
            periode={periode}
            ubah={setPeriode}
            loket={loket}
            ubahLoket={setLoket}
            daftarLoket={daftarLoket?.map((l) => ({ kunci: l.kunci, nama: l.nama })) ?? []}
          />
        ) : (
          <Skeleton className="h-20 rounded-xl" />
        )}
      </div>

      {isError ? (
        <Alert variant="destructive">
          <IconAlertTriangle />
          <AlertTitle>Nomoratur gagal dimuat</AlertTitle>
          <AlertDescription>
            <p>{(error as Error).message}</p>
            <Button variant="outline" size="sm" className="mt-2" onClick={() => void refetch()}>
              Coba lagi
            </Button>
          </AlertDescription>
        </Alert>
      ) : !data || !periode ? (
        <div className="grid gap-4 md:gap-6">
          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            {[0, 1, 2, 3].map((i) => (
              <Skeleton key={i} className="h-28 rounded-xl" />
            ))}
          </div>
          <Skeleton className="h-80 rounded-xl" />
        </div>
      ) : kosong ? (
        <Card>
          <CardContent>
            <Empty>
              <EmptyHeader>
                <EmptyMedia variant="icon">
                  <IconNumbers />
                </EmptyMedia>
                <EmptyTitle>Tidak ada transaksi</EmptyTitle>
                <EmptyDescription>Belum ada berkas STS yang diimpor untuk periode{loket === SEMUA ? "" : " dan loket"} ini.</EmptyDescription>
              </EmptyHeader>
            </Empty>
          </CardContent>
        </Card>
      ) : (
        <div className={cn("grid grid-cols-1 gap-4 transition-opacity md:gap-6", isFetching && "opacity-60")} aria-busy={isFetching}>
          {data.ringkasan.belumTercatat > 0 && (
            <Alert>
              <IconInfoCircle />
              <AlertTitle>{angka(data.ringkasan.belumTercatat)} transaksi belum tercatat nomoraturnya</AlertTitle>
              <AlertDescription>
                Transaksi itu diimpor sebelum nomoratur dicatat. Impor ulang berkas STS-nya di halaman Laporan untuk melengkapinya; penerimaannya
                tidak terhitung dua kali.
              </AlertDescription>
            </Alert>
          )}
          <Ringkasan laporan={data} />
          <PerLoket laporan={data} pilih={setLoket} />
          {data.loket.map((l) =>
            periode.mode === "harian" ? (
              <RincianHarian key={l.nilai} loket={l} hari={l.hari[0]} />
            ) : (
              <RincianBulanan key={l.nilai} loket={l} bukaHari={bukaHari} />
            )
          )}
          <DaftarGanda laporan={data} />
        </div>
      )}
    </Halaman>
  )
}

/* ---------- Periode dan loket ---------- */

function PanelPeriode({
  periode,
  ubah,
  loket,
  ubahLoket,
  daftarLoket,
}: {
  periode: Periode
  ubah: (p: Periode) => void
  loket: string
  ubahLoket: (l: string) => void
  daftarLoket: { kunci: string; nama: string }[]
}) {
  const hariIni = hariIniIso()
  return (
    <Card className="gap-4 py-4">
      <CardContent className="flex flex-wrap items-center gap-x-4 gap-y-3 px-4">
        <ToggleGroup
          type="single"
          variant="outline"
          value={periode.mode}
          onValueChange={(m) => m && ubah(gantiMode(periode, m as Periode["mode"], hariIni))}
          aria-label="Jenis laporan"
        >
          <ToggleGroupItem value="harian" className="px-3">
            Harian
          </ToggleGroupItem>
          <ToggleGroupItem value="bulanan" className="px-3">
            Bulanan
          </ToggleGroupItem>
        </ToggleGroup>
        <div className="flex items-center gap-1">
          <Button
            variant="outline"
            size="icon"
            aria-label={periode.mode === "harian" ? "Hari sebelumnya" : "Bulan sebelumnya"}
            onClick={() => ubah(geserPeriode(periode, -1))}
          >
            <IconChevronLeft />
          </Button>
          {periode.mode === "harian" ? (
            <Input
              type="date"
              aria-label="Tanggal laporan"
              value={periode.dari}
              max={hariIni}
              onChange={(e) => e.target.value && ubah({ mode: "harian", dari: e.target.value, sampai: e.target.value })}
              className="w-auto"
            />
          ) : (
            <Input
              type="month"
              aria-label="Bulan laporan"
              value={periode.dari.slice(0, 7)}
              max={hariIni.slice(0, 7)}
              onChange={(e) => e.target.value && ubah(periodeBulan(e.target.value))}
              className="w-auto"
            />
          )}
          <Button
            variant="outline"
            size="icon"
            aria-label={periode.mode === "harian" ? "Hari berikutnya" : "Bulan berikutnya"}
            disabled={periode.sampai >= hariIni}
            onClick={() => ubah(geserPeriode(periode, 1))}
          >
            <IconChevronRight />
          </Button>
        </div>
        <Select value={loket} onValueChange={ubahLoket}>
          <SelectTrigger className="w-auto min-w-44" aria-label="Loket">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value={SEMUA}>Semua loket</SelectItem>
            {daftarLoket.map((l) => (
              <SelectItem key={l.kunci} value={l.kunci}>
                {l.nama}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
        <p className="text-sm font-medium sm:ml-auto" aria-live="polite">
          {labelPeriode(periode.dari, periode.sampai)}
        </p>
      </CardContent>
    </Card>
  )
}

/* ---------- Ringkasan ---------- */

function Ringkasan({ laporan }: { laporan: LaporanNomoratur }) {
  const r = laporan.ringkasan
  return (
    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <KartuAngka judul="Lembar SKPD terpakai" nilai={angka(r.lembar)} catatan={`${angka(r.transaksi)} transaksi bernomoratur`} />
      <KartuAngka judul="Tanpa nomoratur" nilai={angka(r.tanpaNomoratur)} catatan="Mis. Mutasi keluar dan Tambahan pajak" />
      <KartuAngka
        judul="Nomor terlewat"
        nilai={angka(r.terlewat)}
        catatan={`Di antara rentang yang berjarak paling banyak ${laporan.batasTerlewat} nomor`}
        tanda={r.terlewat > 0}
      />
      <KartuAngka judul="Nomor ganda" nilai={angka(r.ganda)} catatan="Dipakai lebih dari sekali dalam periode ini" tanda={r.ganda > 0} />
    </div>
  )
}

function KartuAngka({ judul, nilai, catatan, tanda }: { judul: string; nilai: string; catatan: string; tanda?: boolean }) {
  return (
    <Card className="@container/card gap-3">
      <CardHeader>
        <CardDescription>{judul}</CardDescription>
        <CardTitle className={cn("text-xl tabular-nums @[250px]/card:text-2xl", tanda && "text-amber-700 dark:text-amber-400")}>{nilai}</CardTitle>
      </CardHeader>
      <CardFooter className="text-sm text-muted-foreground">{catatan}</CardFooter>
    </Card>
  )
}

function PerLoket({ laporan, pilih }: { laporan: LaporanNomoratur; pilih: (l: string) => void }) {
  const adaBelum = laporan.ringkasan.belumTercatat > 0
  const r = laporan.ringkasan
  return (
    <Card className="gap-4">
      <CardHeader>
        <CardTitle className="text-base">Per loket</CardTitle>
        <CardDescription>Total {labelPeriode(laporan.dari, laporan.sampai)}. Nama loket menampilkan loket itu saja.</CardDescription>
      </CardHeader>
      <CardContent className="px-0 sm:px-6">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Loket</TableHead>
              <TableHead className="text-right">Lembar SKPD</TableHead>
              <TableHead className="text-right">Tanpa nomoratur</TableHead>
              <TableHead className="text-right">Terlewat</TableHead>
              <TableHead className="text-right">Ganda</TableHead>
              {adaBelum && <TableHead className="text-right">Belum tercatat</TableHead>}
            </TableRow>
          </TableHeader>
          <TableBody>
            {laporan.loket.map((l) => (
              <TableRow key={l.nilai}>
                <TableCell>
                  <button type="button" className="text-left font-medium underline-offset-4 hover:underline" onClick={() => pilih(l.nilai)}>
                    {l.nama}
                  </button>
                </TableCell>
                <TableCell className="text-right tabular-nums">{angka(l.lembar)}</TableCell>
                <TableCell className="text-right tabular-nums">{angka(l.tanpaNomoratur)}</TableCell>
                <TableCell className="text-right tabular-nums">{angka(l.terlewat)}</TableCell>
                <TableCell className="text-right tabular-nums">{angka(l.ganda)}</TableCell>
                {adaBelum && <TableCell className="text-right tabular-nums">{angka(l.belumTercatat)}</TableCell>}
              </TableRow>
            ))}
          </TableBody>
          {laporan.loket.length > 1 && (
            <TableFooter>
              <TableRow>
                <TableCell>Semua loket</TableCell>
                <TableCell className="text-right tabular-nums">{angka(r.lembar)}</TableCell>
                <TableCell className="text-right tabular-nums">{angka(r.tanpaNomoratur)}</TableCell>
                <TableCell className="text-right tabular-nums">{angka(r.terlewat)}</TableCell>
                <TableCell className="text-right tabular-nums">{angka(r.ganda)}</TableCell>
                {adaBelum && <TableCell className="text-right tabular-nums">{angka(r.belumTercatat)}</TableCell>}
              </TableRow>
            </TableFooter>
          )}
        </Table>
      </CardContent>
    </Card>
  )
}

/* ---------- Rincian ---------- */

function LencanaLoket({ loket }: { loket: Pick<LoketNomoratur, "terlewat" | "ganda"> }) {
  return (
    <CardAction className="flex flex-wrap justify-end gap-1.5">
      {loket.terlewat > 0 && <Badge variant="outline">{angka(loket.terlewat)} terlewat</Badge>}
      {loket.ganda > 0 && <Badge variant="outline">{angka(loket.ganda)} ganda</Badge>}
    </CardAction>
  )
}

/** Satu hari satu loket: setiap rentang, nomor terlewat, dan nomor ganda */
function RincianHarian({ loket, hari }: { loket: LoketNomoratur; hari: HariNomoratur | undefined }) {
  if (!hari) return null
  return (
    <Card className="gap-4">
      <CardHeader>
        <CardTitle className="text-base">{loket.nama}</CardTitle>
        <CardDescription>
          {angka(hari.lembar)} lembar SKPD dalam {angka(hari.rentang.length)} rentang
          {hari.tanpaNomoratur > 0 && ` · ${angka(hari.tanpaNomoratur)} transaksi tanpa nomoratur`}
          {hari.belumTercatat > 0 && ` · ${angka(hari.belumTercatat)} belum tercatat`}
        </CardDescription>
        <LencanaLoket loket={loket} />
      </CardHeader>
      <CardContent className="grid gap-4 px-0 sm:px-6">
        {hari.rentang.length > 0 && (
          <Table aria-label={`Rentang nomoratur ${loket.nama}`}>
            <TableHeader>
              <TableRow>
                <TableHead className="w-12">No.</TableHead>
                <TableHead>Nomor awal</TableHead>
                <TableHead>Nomor akhir</TableHead>
                <TableHead className="text-right">Lembar</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {hari.rentang.map((r, i) => (
                <TableRow key={r.awal}>
                  <TableCell className="text-muted-foreground tabular-nums">{i + 1}</TableCell>
                  <TableCell className="font-mono tabular-nums">{r.awal}</TableCell>
                  <TableCell className="font-mono tabular-nums">{r.akhir}</TableCell>
                  <TableCell className="text-right tabular-nums">{angka(r.lembar)}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}
        <DaftarNomor judul="Nomor terlewat" isi={hari.terlewat.map((t) => `${labelRentang(t)} (${angka(t.jumlah)})`)} />
        <DaftarNomor judul="Nomor ganda" isi={hari.ganda} keterangan="Dipakai lebih dari sekali; rinciannya ada di daftar nomor ganda." />
      </CardContent>
    </Card>
  )
}

function DaftarNomor({ judul, isi, keterangan }: { judul: string; isi: string[]; keterangan?: string }) {
  if (isi.length === 0) return null
  return (
    <div className="grid gap-2 px-4 sm:px-0">
      <p className="text-sm font-medium">{judul}</p>
      {keterangan && <p className="text-xs text-muted-foreground">{keterangan}</p>}
      <ul className="flex flex-wrap gap-1.5" role="list">
        {isi.map((n) => (
          <li key={n}>
            <Badge variant="secondary" className="font-mono tabular-nums">
              {n}
            </Badge>
          </li>
        ))}
      </ul>
    </div>
  )
}

/** Satu loket sebulan: satu baris per hari dengan rentangnya diringkas; tanggal membuka laporan harian */
function RincianBulanan({ loket, bukaHari }: { loket: LoketNomoratur; bukaHari: (t: string) => void }) {
  return (
    <Card className="gap-4">
      <CardHeader>
        <CardTitle className="text-base">{loket.nama}</CardTitle>
        <CardDescription>
          {angka(loket.lembar)} lembar SKPD dalam {angka(loket.hari.length)} hari
          {loket.tanpaNomoratur > 0 && ` · ${angka(loket.tanpaNomoratur)} transaksi tanpa nomoratur`}
        </CardDescription>
        <LencanaLoket loket={loket} />
      </CardHeader>
      <CardContent className="px-0 sm:px-6">
        <Table aria-label={`Nomoratur ${loket.nama} per hari`}>
          <TableHeader>
            <TableRow>
              <TableHead>Tanggal</TableHead>
              <TableHead className="text-right">Lembar</TableHead>
              <TableHead>Rentang</TableHead>
              <TableHead className="text-right">Terlewat</TableHead>
              <TableHead className="text-right">Ganda</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {loket.hari.map((h) => {
              const terlewat = h.terlewat.reduce((n, t) => n + t.jumlah, 0)
              const sisa = h.rentang.length - RENTANG_RINGKAS
              return (
                <TableRow key={h.tanggal}>
                  <TableCell>
                    <button type="button" className="font-medium underline-offset-4 hover:underline" onClick={() => bukaHari(h.tanggal)}>
                      {labelTanggal(h.tanggal)}
                    </button>
                  </TableCell>
                  <TableCell className="text-right tabular-nums">{angka(h.lembar)}</TableCell>
                  <TableCell className="min-w-56 font-mono text-xs whitespace-normal tabular-nums">
                    {h.rentang.length === 0
                      ? "—"
                      : h.rentang.slice(0, RENTANG_RINGKAS).map(labelRentang).join(", ") + (sisa > 0 ? `, +${angka(sisa)} rentang lain` : "")}
                  </TableCell>
                  <TableCell className="text-right tabular-nums">{angka(terlewat)}</TableCell>
                  <TableCell className="text-right tabular-nums">{angka(h.ganda.length)}</TableCell>
                </TableRow>
              )
            })}
          </TableBody>
          <TableFooter>
            <TableRow>
              <TableCell>Jumlah</TableCell>
              <TableCell className="text-right tabular-nums">{angka(loket.lembar)}</TableCell>
              <TableCell />
              <TableCell className="text-right tabular-nums">{angka(loket.terlewat)}</TableCell>
              <TableCell className="text-right tabular-nums">{angka(loket.ganda)}</TableCell>
            </TableRow>
          </TableFooter>
        </Table>
      </CardContent>
    </Card>
  )
}

/** Nomor yang dipakai lebih dari sekali dalam periode, dengan tempat dan tanggal pemakaiannya */
function DaftarGanda({ laporan }: { laporan: LaporanNomoratur }) {
  if (laporan.ganda.length === 0) return null
  return (
    <Card className="gap-4">
      <CardHeader>
        <CardTitle className="text-base">Nomor ganda</CardTitle>
        <CardDescription>
          Nomoratur yang tercatat pada lebih dari satu transaksi {labelPeriode(laporan.dari, laporan.sampai)}
          {laporan.loket.length === 1 ? ` di ${laporan.loket[0].nama}` : ""}.
        </CardDescription>
      </CardHeader>
      <CardContent className="px-0 sm:px-6">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Nomor</TableHead>
              <TableHead className="text-right">Dipakai</TableHead>
              <TableHead>Pemakaian</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {laporan.ganda.map((g) => (
              <TableRow key={g.nomor}>
                <TableCell className="font-mono tabular-nums">{g.nomor}</TableCell>
                <TableCell className="text-right tabular-nums">{angka(g.kali)}×</TableCell>
                <TableCell className="whitespace-normal">
                  <ul className="grid gap-0.5" role="list">
                    {g.pemakaian.map((p, i) => (
                      <li key={i} className="text-sm">
                        {p.loket} · {labelTanggal(p.tanggal)} · <span className="text-muted-foreground">{p.jenisDaftar}</span>
                      </li>
                    ))}
                  </ul>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  )
}
