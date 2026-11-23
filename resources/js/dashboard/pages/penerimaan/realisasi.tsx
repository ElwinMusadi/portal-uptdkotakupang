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
import {
  cetakFormI,
  kodeBulan,
  labelBulan,
  NAMA_BULAN,
  rentangBulanIni,
  RingkasanRealisasi,
  SaldoKotaKosong,
  Sumber,
  TabelFormI,
  TertutupSaldo,
} from "@/components/penerimaan/form-i"
import { NavPenerimaan } from "@/components/penerimaan/nav-penerimaan"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Separator } from "@/components/ui/separator"
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from "@/components/ui/sheet"
import { Skeleton } from "@/components/ui/skeleton"
import { Spinner } from "@/components/ui/spinner"
import { Switch } from "@/components/ui/switch"
import { ToggleGroup, ToggleGroupItem } from "@/components/ui/toggle-group"
import { ApiError } from "@/lib/api"
import { hariIniIso, rupiah, tanggalJam } from "@/lib/format"
import {
  CAKUPAN_REALISASI,
  LABEL_BAGIAN,
  unduhRealisasi,
  useRealisasi,
  useSimpanTarget,
  type BagianRealisasi,
  type CakupanRealisasi,
  type KunciTarget,
  type TargetPenerimaan,
} from "@/lib/penerimaan"
import { cn } from "@/lib/utils"

import { akhirBulan, geserPeriode, periodeBulan } from "../../../portal/penerimaan/periode"

/* ---------- Bulan ---------- */

const geserBulan = (bulan: string, n: number) => geserPeriode(periodeBulan(bulan), n).dari.slice(0, 7)

/* ---------- Halaman ---------- */

export default function HalamanRealisasi() {
  const [bulan, setBulan] = React.useState(() => hariIniIso().slice(0, 7))
  // Kosong berarti sebulan penuh; tanggal batas berlaku untuk bulan yang sedang dipilih saja (D-070)
  const [sampai, setSampai] = React.useState("")
  // Form I gabungan atau Form I.I Kota Kupang (D-101); bulan dan tanggal batas berlaku untuk keduanya
  const [cakupan, setCakupan] = React.useState<CakupanRealisasi>("gabungan")
  const { data, isError, error, isFetching, refetch } = useRealisasi(bulan, sampai, cakupan)
  const [ubahTarget, setUbahTarget] = React.useState(false)
  const [mengunduh, setMengunduh] = React.useState(false)
  const tabel = React.useRef<HTMLTableElement>(null)

  const tahun = Number(bulan.slice(0, 4))
  // Selama bulan lain dimuat, laporan sebelumnya tetap tampil redup
  const laporan = data
  const sesuai = !!data && kodeBulan(data.tahun, data.bulan) === bulan && (data.sampai ?? "") === sampai && data.cakupan === cakupan
  const siap = sesuai && data.tersedia && !data.saldoKosong
  const form = CAKUPAN_REALISASI[laporan?.cakupan ?? cakupan]
  // Target baru bisa diubah setelah target tahun itu termuat, agar isian kosong tidak menimpanya
  const targetTermuat = !!data && data.tahun === tahun

  const pilihBulan = (b: string) => {
    setBulan(b)
    setSampai("")
  }

  const unduh = async () => {
    setMengunduh(true)
    try {
      await unduhRealisasi(bulan, sampai, cakupan)
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
      <PengantarHalaman deskripsi="Realisasi PKB, BBNKB, dan dendanya dari berkas STS semua loket terhadap target tahunan: Form I gabungan, termasuk kendaraan kabupaten lain yang membayar di loket UPTD, dan Form I.I khusus kendaraan ber-UPT asal Kota Kupang. Opsen kabupaten/kota tidak termasuk.">
        <Button onClick={() => setUbahTarget(true)} disabled={!targetTermuat}>
          <IconTargetArrow />
          Target {tahun}
        </Button>
      </PengantarHalaman>

      <PilihBulan bulan={bulan} ubah={pilihBulan} sampai={sampai} ubahSampai={setSampai} cakupan={cakupan} ubahCakupan={setCakupan}>
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
          {laporan.tersedia && laporan.saldoKosong ? (
            <SaldoKotaKosong laporan={laporan} isi={targetTermuat ? () => setUbahTarget(true) : undefined} />
          ) : laporan.tersedia ? (
            <>
              <RingkasanRealisasi laporan={laporan} />
              <Card className="gap-4">
                <CardHeader>
                  <CardTitle className="text-base">
                    {form.form} · {rentangBulanIni(laporan.tahun, laporan.bulan, laporan.sampai)}
                  </CardTitle>
                  <CardDescription>
                    Realisasi penerimaan {form.isi}, dari semua loket. Sama seperti berkas Excel dan hasil cetaknya.
                  </CardDescription>
                </CardHeader>
                <CardContent className="px-0 sm:px-6">
                  <TabelFormI laporan={laporan} ref={tabel} />
                </CardContent>
              </Card>
              <Sumber laporan={laporan} />
            </>
          ) : (
            <TertutupSaldo laporan={laporan} ubah={pilihBulan} />
          )}
        </div>
      )}

      {ubahTarget && targetTermuat && <SheetTarget tahun={tahun} target={data.target} tutup={() => setUbahTarget(false)} />}
    </Halaman>
  )
}

/**
 * Form (gabungan atau Kota Kupang, D-101), bulan laporan dengan tombol bulan sebelumnya
 * dan berikutnya, tanggal batasnya bila Form I dibuat di tengah bulan (D-070), lalu
 * aksi untuk Form I itu
 */
function PilihBulan({
  bulan,
  ubah,
  sampai,
  ubahSampai,
  cakupan,
  ubahCakupan,
  children,
}: {
  bulan: string
  ubah: (b: string) => void
  sampai: string
  ubahSampai: (t: string) => void
  cakupan: CakupanRealisasi
  ubahCakupan: (c: CakupanRealisasi) => void
  children: React.ReactNode
}) {
  const [tahun, nomor] = bulan.split("-").map(Number)
  const idSampai = React.useId()
  return (
    <Card className="gap-4 py-4">
      <CardContent className="flex flex-wrap items-center gap-x-4 gap-y-3 px-4">
        <ToggleGroup
          type="single"
          variant="outline"
          value={cakupan}
          onValueChange={(c) => c && ubahCakupan(c as CakupanRealisasi)}
          aria-label="Realisasi"
        >
          {(Object.keys(CAKUPAN_REALISASI) as CakupanRealisasi[]).map((c) => (
            <ToggleGroupItem key={c} value={c} className="px-3">
              {CAKUPAN_REALISASI[c].label}
            </ToggleGroupItem>
          ))}
        </ToggleGroup>
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
        <div className="flex flex-wrap items-center gap-2">
          <Label htmlFor={idSampai} className="text-sm font-normal text-muted-foreground">
            Sampai tanggal
          </Label>
          <Input
            id={idSampai}
            type="date"
            value={sampai}
            min={`${bulan}-01`}
            max={akhirBulan(bulan)}
            onChange={(e) => ubahSampai(e.target.value)}
            className="w-auto"
          />
          {sampai && (
            <Button variant="ghost" size="sm" onClick={() => ubahSampai("")}>
              Sebulan penuh
            </Button>
          )}
        </div>
        <p className="text-sm font-medium" aria-live="polite">
          {rentangBulanIni(tahun, nomor, sampai || null)}
        </p>
        <div className="flex flex-wrap items-center gap-2 sm:ml-auto">{children}</div>
      </CardContent>
    </Card>
  )
}

/* ---------- Target dan saldo awal ---------- */

const ISIAN_TARGET: { kunci: KunciTarget; label: string; keterangan?: string }[] = [
  { kunci: "pkb", label: "PKB", keterangan: "Termasuk PKB tunggakan." },
  { kunci: "bbn", label: "BBNKB" },
  { kunci: "dendaPkb", label: "Denda PKB", keterangan: "Termasuk denda PKB tunggakan." },
  { kunci: "dendaBbn", label: "Denda BBNKB" },
]

const BAGIAN: BagianRealisasi[] = ["pkb", "tunggakan", "bbn", "dendaPkb", "dendaTunggakan", "dendaBbn"]

/** Enam angka saldo awal; yang belum ada ditulis nol */
const angkaBagian = (sumber: Partial<Record<BagianRealisasi, number>> | null | undefined) =>
  Object.fromEntries(BAGIAN.map((b) => [b, sumber?.[b] ?? 0])) as Record<BagianRealisasi, number>

function SheetTarget({ tahun, target, tutup }: { tahun: number; target: TargetPenerimaan | null; tutup: () => void }) {
  const simpan = useSimpanTarget()
  const [isian, setIsian] = React.useState<Record<KunciTarget, number>>({
    pkb: target?.pkb ?? 0,
    bbn: target?.bbn ?? 0,
    dendaPkb: target?.dendaPkb ?? 0,
    dendaBbn: target?.dendaBbn ?? 0,
  })
  const [adaSaldo, setAdaSaldo] = React.useState(!!target?.saldoAwal)
  const [sampaiBulan, setSampaiBulan] = React.useState(target?.saldoAwal?.sampaiBulan ?? 9)
  const [saldo, setSaldo] = React.useState(() => angkaBagian(target?.saldoAwal))
  // Saldo awal Form I.I untuk bulan yang sama (D-101); tahun yang disimpan sebelumnya belum memilikinya
  const [saldoKota, setSaldoKota] = React.useState(() => angkaBagian(target?.saldoAwal?.kotaKupang))
  const kotaBelumDiisi = !!target?.saldoAwal && !target.saldoAwal.kotaKupang
  const [galat, setGalat] = React.useState<{ pesan: string; isian: Record<string, string> } | null>(null)
  const id = React.useId()

  const pajak = isian.pkb + isian.bbn
  const denda = isian.dendaPkb + isian.dendaBbn

  const kirim = async (e: React.FormEvent) => {
    e.preventDefault()
    setGalat(null)
    try {
      await simpan.mutateAsync({
        tahun,
        isian: { target: isian, saldoAwal: adaSaldo ? { sampaiBulan, ...saldo, kotaKupang: saldoKota } : null },
      })
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
              Target tahunan Form I dan Form I.I. Laporan setiap bulan tahun {tahun} memakai target terakhir yang disimpan.
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
                    <Select value={String(sampaiBulan)} onValueChange={(v) => setSampaiBulan(Number(v))}>
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
                      Form I dan Form I.I per bulan tersedia mulai{" "}
                      {sampaiBulan < 12 ? labelBulan(tahun, sampaiBulan + 1) : `tahun ${tahun + 1}`}.
                    </p>
                    {pesanIsian("saldoAwal.sampaiBulan")}
                  </div>
                  <IsianSaldo
                    judul="Gabungan (Form I)"
                    keterangan="Semua transaksi di loket UPTD, termasuk kendaraan kabupaten lain."
                    awalan="saldoAwal"
                    nilai={saldo}
                    ubah={setSaldo}
                    galat={galat?.isian ?? {}}
                    pesanIsian={pesanIsian}
                    idGalat={(nama) => `${id}-${nama}-galat`}
                  />
                  <IsianSaldo
                    judul="Kota Kupang (Form I.I)"
                    keterangan={`Hanya kendaraan ber-UPT asal Kota Kupang, untuk bulan yang sama; misalnya kolom S/D bulan lalu Form I.I ${sampaiBulan < 12 ? labelBulan(tahun, sampaiBulan + 1) : `Desember ${tahun}`}.`}
                    peringatan={kotaBelumDiisi ? "Belum pernah diisi. Form I.I tidak menampilkan angka sampai saldo ini disimpan." : undefined}
                    awalan="saldoAwal.kotaKupang"
                    nilai={saldoKota}
                    ubah={setSaldoKota}
                    galat={galat?.isian ?? {}}
                    pesanIsian={pesanIsian}
                    idGalat={(nama) => `${id}-${nama}-galat`}
                  />
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

/** Enam angka saldo awal satu form, dengan judul kelompok dan pesan galat per isian */
function IsianSaldo({
  judul,
  keterangan,
  peringatan,
  awalan,
  nilai,
  ubah,
  galat,
  pesanIsian,
  idGalat,
}: {
  judul: string
  keterangan: string
  peringatan?: string
  awalan: string
  nilai: Record<BagianRealisasi, number>
  ubah: React.Dispatch<React.SetStateAction<Record<BagianRealisasi, number>>>
  galat: Record<string, string>
  pesanIsian: (nama: string) => React.ReactNode
  idGalat: (nama: string) => string
}) {
  const id = React.useId()
  return (
    <fieldset className="grid gap-4 rounded-lg border p-3" aria-describedby={`${id}-ket`}>
      <legend className="px-1 text-sm font-semibold">{judul}</legend>
      <div className="-mt-2 grid gap-1">
        <p id={`${id}-ket`} className="text-xs text-muted-foreground">
          {keterangan}
        </p>
        {peringatan && <p className="text-xs font-medium text-amber-700 dark:text-amber-400">{peringatan}</p>}
        {pesanIsian(awalan)}
      </div>
      {BAGIAN.map((b) => {
        const nama = `${awalan}.${b}`
        return (
          <div key={b} className="grid gap-2">
            <Label htmlFor={`${id}-${b}`}>{LABEL_BAGIAN[b]}</Label>
            <InputRupiah
              id={`${id}-${b}`}
              nilai={nilai[b]}
              ubah={(n) => ubah((s) => ({ ...s, [b]: n }))}
              invalid={!!galat[nama]}
              describedBy={galat[nama] ? idGalat(nama) : undefined}
            />
            {pesanIsian(nama)}
          </div>
        )
      })}
    </fieldset>
  )
}
