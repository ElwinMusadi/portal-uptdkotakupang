import * as React from "react"
import { IconChevronLeft, IconChevronRight, IconDownload } from "@tabler/icons-react"
import { toast } from "sonner"

import { NavAntrean } from "@/components/antrean/nav-antrean"
import { Halaman, PengantarHalaman } from "@/components/data/page"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Skeleton } from "@/components/ui/skeleton"
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { ToggleGroup, ToggleGroupItem } from "@/components/ui/toggle-group"
import {
  csvRingkasan,
  durasi,
  MAKS_HARI_RINGKASAN,
  tiketRingkasan,
  unduhTeks,
  useRingkasanAntrean,
  type JumlahAntrean,
  type RingkasanAntrean,
} from "@/lib/antrean"
import { angka, hariIniIso, tanggal } from "@/lib/format"

import { gantiMode, geserPeriode, jumlahHari, labelPeriode, periodeBulan, TANGGAL_ISO, type Mode, type Periode } from "../../../portal/penerimaan/periode"

/*
 * Ringkasan Antrean Online (D-051 Irisan 4): satu hari, satu bulan, atau
 * rentang sampai 31 hari (keputusan pemilik). Jumlah nomor per layanan dan
 * per hari, dan per tahap berapa yang selesai, dilewati otomatis, ditunda,
 * serta nilai tengah waktu tunggu dan lama di tahap. Unduhan CSV memuat juga
 * daftar nomor dengan nomor polisinya (keputusan pemilik).
 */

function galatPeriode(p: Periode): string | null {
  if (!TANGGAL_ISO.test(p.dari) || !TANGGAL_ISO.test(p.sampai)) return "Pilih tanggal ringkasan."
  if (p.sampai < p.dari) return "Tanggal akhir tidak boleh sebelum tanggal awal."
  if (jumlahHari(p.dari, p.sampai) > MAKS_HARI_RINGKASAN) return `Rentang ringkasan paling lama ${MAKS_HARI_RINGKASAN} hari.`
  return null
}

export default function HalamanRingkasan() {
  const hariIni = hariIniIso()
  const [periode, setPeriode] = React.useState<Periode>({ mode: "harian", dari: hariIni, sampai: hariIni })
  const galat = galatPeriode(periode)
  const ringkasan = useRingkasanAntrean(periode.dari, periode.sampai, !galat)
  const r = ringkasan.data

  return (
    <Halaman>
      <NavAntrean />
      <PengantarHalaman deskripsi="Jumlah nomor antrean dan waktu layanan per tahap. Nomor dihitung pada tanggal dicetak, termasuk tahap yang selesai pada hari berikutnya." />
      <PanelPeriode periode={periode} ubah={setPeriode} galat={galat} ringkasan={galat ? undefined : r} />
      {galat ? null : ringkasan.isLoading ? (
        <Skeleton className="h-64" />
      ) : ringkasan.error || !r ? (
        <p className="text-sm text-destructive">Ringkasan gagal dimuat: {ringkasan.error?.message}</p>
      ) : (
        <>
          <div className="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5">
            <Angka label="Dicetak" nilai={r.jumlah.dicetak} />
            <Angka label="Selesai" nilai={r.jumlah.selesai} />
            <Angka label="Dibatalkan" nilai={r.jumlah.batal} />
            <Angka label="Masih berjalan" nilai={r.jumlah.berjalan} />
            <Angka label="Pernah ditunda" nilai={r.jumlah.ditunda} />
          </div>
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Per layanan</CardTitle>
            </CardHeader>
            <CardContent>
              <TabelJumlah judul="Layanan" baris={r.layanan.map((l) => ({ kunci: l.kode, label: `${l.kode} – ${l.nama}`, ...l }))} jumlah={r.jumlah} />
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Per tahap</CardTitle>
              <CardDescription>
                Waktu ditulis sebagai nilai tengah, agar berkas yang menginap tidak membuat angkanya melonjak. Waktu hanya diukur dari saat yang tercatat (cetak atau tahap yang ditandai petugas);
                tahap yang dilewati otomatis tidak diukur.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Tahap</TableHead>
                      <TableHead className="text-right">Ditandai selesai</TableHead>
                      <TableHead className="text-right">Dilewati otomatis</TableHead>
                      <TableHead className="text-right">Pernah ditunda</TableHead>
                      <TableHead className="text-right">Tunggu dipanggil</TableHead>
                      <TableHead className="text-right">Lama di tahap</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {r.tahap.map((t) => (
                      <TableRow key={t.kunci}>
                        <TableCell className="whitespace-nowrap">{t.nama}</TableCell>
                        <TableCell className="text-right tabular-nums">{angka(t.selesai)}</TableCell>
                        <TableCell className="text-right tabular-nums">{angka(t.otomatis)}</TableCell>
                        <TableCell className="text-right tabular-nums">{t.dipanggil ? angka(t.ditunda) : "—"}</TableCell>
                        <TableCell className="text-right whitespace-nowrap tabular-nums">{t.dipanggil ? durasi(t.tunggu) : "—"}</TableCell>
                        <TableCell className="text-right whitespace-nowrap tabular-nums">{durasi(t.lama)}</TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>
            </CardContent>
          </Card>
          {r.dari !== r.sampai && (
            <Card>
              <CardHeader>
                <CardTitle className="text-base">Per hari</CardTitle>
              </CardHeader>
              <CardContent>
                <TabelJumlah judul="Tanggal" baris={r.harian.map((h) => ({ kunci: h.tanggal, label: tanggal(h.tanggal), ...h }))} jumlah={r.jumlah} />
              </CardContent>
            </Card>
          )}
        </>
      )}
    </Halaman>
  )
}

function Angka({ label, nilai }: { label: string; nilai: number }) {
  return (
    <Card className="gap-1 py-4">
      <CardContent className="grid gap-1 px-4">
        <p className="text-xs text-muted-foreground">{label}</p>
        <p className="text-2xl font-semibold tabular-nums">{angka(nilai)}</p>
      </CardContent>
    </Card>
  )
}

function TabelJumlah({ judul, baris, jumlah }: { judul: string; baris: (JumlahAntrean & { kunci: string; label: string })[]; jumlah: JumlahAntrean }) {
  const sel = (j: JumlahAntrean) => (
    <>
      <TableCell className="text-right tabular-nums">{angka(j.dicetak)}</TableCell>
      <TableCell className="text-right tabular-nums">{angka(j.selesai)}</TableCell>
      <TableCell className="text-right tabular-nums">{angka(j.batal)}</TableCell>
      <TableCell className="text-right tabular-nums">{angka(j.berjalan)}</TableCell>
    </>
  )
  return (
    <div className="overflow-x-auto">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>{judul}</TableHead>
            <TableHead className="text-right">Dicetak</TableHead>
            <TableHead className="text-right">Selesai</TableHead>
            <TableHead className="text-right">Dibatalkan</TableHead>
            <TableHead className="text-right">Masih berjalan</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {baris.map((b) => (
            <TableRow key={b.kunci}>
              <TableCell className="whitespace-nowrap">{b.label}</TableCell>
              {sel(b)}
            </TableRow>
          ))}
        </TableBody>
        <TableFooter>
          <TableRow>
            <TableCell>Jumlah</TableCell>
            {sel(jumlah)}
          </TableRow>
        </TableFooter>
      </Table>
    </div>
  )
}

function PanelPeriode({ periode, ubah, galat, ringkasan }: { periode: Periode; ubah: (p: Periode) => void; galat: string | null; ringkasan: RingkasanAntrean | undefined }) {
  const hariIni = hariIniIso()
  const galatId = React.useId()
  const [mengunduh, setMengunduh] = React.useState(false)

  const unduh = async () => {
    if (!ringkasan) return
    setMengunduh(true)
    try {
      const tiket = await tiketRingkasan(ringkasan.dari, ringkasan.sampai)
      const nama = ringkasan.dari === ringkasan.sampai ? ringkasan.dari : `${ringkasan.dari}_${ringkasan.sampai}`
      unduhTeks(csvRingkasan(ringkasan, tiket, labelPeriode(ringkasan.dari, ringkasan.sampai)), `ringkasan-antrean-${nama}.csv`)
    } catch (e) {
      toast.error("CSV gagal dibuat", { description: (e as Error).message })
    } finally {
      setMengunduh(false)
    }
  }

  return (
    <Card className="gap-4 py-4">
      <CardContent className="grid gap-3 px-4">
        <div className="flex flex-wrap items-center gap-2">
          <ToggleGroup type="single" variant="outline" value={periode.mode} onValueChange={(m) => m && ubah(gantiMode(periode, m as Mode, hariIni))} aria-label="Jenis ringkasan">
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
                onChange={(e) => ubah({ ...periode, dari: e.target.value })}
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
                onChange={(e) => ubah({ ...periode, sampai: e.target.value })}
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
                onClick={() => ubah(geserPeriode(periode, -1))}
              >
                <IconChevronLeft />
              </Button>
              {periode.mode === "harian" ? (
                <Input
                  type="date"
                  aria-label="Tanggal ringkasan"
                  aria-invalid={!!galat}
                  aria-describedby={galat ? galatId : undefined}
                  value={periode.dari}
                  max={hariIni}
                  onChange={(e) => ubah({ mode: "harian", dari: e.target.value, sampai: e.target.value })}
                  className="w-auto"
                />
              ) : (
                <Input
                  type="month"
                  aria-label="Bulan ringkasan"
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
                disabled={!!galat || periode.sampai >= hariIni}
                onClick={() => ubah(geserPeriode(periode, 1))}
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
          <p id={galatId} className="text-sm text-destructive" role="alert">
            {galat}
          </p>
        )}
        <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
          <Button variant="outline" size="sm" disabled={!ringkasan || mengunduh} onClick={() => void unduh()}>
            <IconDownload />
            {mengunduh ? "Menyiapkan CSV…" : "Unduh CSV"}
          </Button>
          <p className="text-xs text-muted-foreground">Berisi ringkasan dan daftar nomor dengan nomor polisi. Simpan dan bagikan dengan hati-hati.</p>
        </div>
      </CardContent>
    </Card>
  )
}
