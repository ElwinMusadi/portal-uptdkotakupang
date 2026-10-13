import * as React from "react"
import {
  IconAdjustments,
  IconCopy,
  IconMinus,
  IconPlus,
  IconRestore,
  IconRosetteDiscountCheck,
} from "@tabler/icons-react"
import { Link } from "react-router"
import { toast } from "sonner"

import { Halaman, PengantarHalaman } from "@/components/data/page"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group"
import { Skeleton } from "@/components/ui/skeleton"
import { Switch } from "@/components/ui/switch"
import type { Tarif } from "@/lib/api"
import { angka, rupiah } from "@/lib/format"
import { hitungPkb, LABEL_KENDARAAN, type JenisKendaraan, type MasukanPkb } from "@/lib/pajak"
import { usePengaturan } from "@/lib/queries"

const MAKS_TELAT = 120

const persenFmt = new Intl.NumberFormat("id-ID", { maximumFractionDigits: 2 })
const persen = (n: number) => `${persenFmt.format(Number.isFinite(n) ? n : 0)}%`

/** Isian rupiah: angka mentah saat diketik, berformat ribuan saat tidak difokus */
function InputRupiah({
  id,
  nilai,
  ubah,
  describedBy,
}: {
  id: string
  nilai: number
  ubah: (n: number) => void
  describedBy?: string
}) {
  const [fokus, setFokus] = React.useState(false)
  const [teks, setTeks] = React.useState("")
  return (
    <div className="relative">
      <span
        aria-hidden="true"
        className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-muted-foreground"
      >
        Rp
      </span>
      <Input
        id={id}
        inputMode="numeric"
        autoComplete="off"
        className="h-11 pl-9 text-lg tabular-nums md:text-lg"
        value={fokus ? teks : angka(nilai)}
        aria-describedby={describedBy}
        onFocus={(e) => {
          setFokus(true)
          setTeks(nilai ? String(nilai) : "")
          const el = e.currentTarget
          requestAnimationFrame(() => el.select())
        }}
        onBlur={() => setFokus(false)}
        onChange={(e) => {
          const bersih = e.target.value.replace(/\D/g, "").replace(/^0+(?=\d)/, "").slice(0, 12)
          setTeks(bersih)
          ubah(bersih ? Number(bersih) : 0)
        }}
      />
    </div>
  )
}

function Kalkulator({ tarif }: { tarif: Tarif }) {
  const awal = React.useCallback(
    (): Required<MasukanPkb> => ({
      pokok: tarif.contohPokok,
      jenis: tarif.contohJenis,
      telat: tarif.contohTelat,
      amnesti: false,
    }),
    [tarif]
  )
  const [m, setM] = React.useState(awal)
  const ubah = (patch: Partial<MasukanPkb>) => setM((x) => ({ ...x, ...patch }))
  const h = hitungPkb(tarif, m)
  const id = React.useId()
  const kenaBatas = m.telat > tarif.maksBulanDenda

  const salin = async () => {
    const baris = [
      "Estimasi tagihan PKB · PINTU Samsat Kota Kupang",
      `${LABEL_KENDARAAN[m.jenis]} · terlambat ${m.telat} bulan${m.amnesti ? " · masa amnesti" : ""}`,
      `Pokok PKB: ${rupiah(h.pokok)}`,
      `Opsen PKB ${persen(tarif.opsenPersen)}: ${rupiah(h.opsen)}`,
      m.amnesti
        ? `Denda keterlambatan: ${rupiah(0)} (dihapus, semula ${rupiah(h.dendaAsli)})`
        : `Denda ${persen(tarif.dendaPersenPerBulan)}/bulan × ${h.bulan} bulan: ${rupiah(h.denda)}`,
      `SWDKLLJ: ${rupiah(h.swd)}`,
      `Total: ${rupiah(h.total)}`,
      "Estimasi. Tagihan resmi mengikuti notis pajak di loket.",
    ]
    try {
      await navigator.clipboard.writeText(baris.join("\n"))
      toast.success("Rincian disalin")
    } catch {
      toast.error("Rincian gagal disalin", { description: "Peramban tidak mengizinkan akses papan klip." })
    }
  }

  return (
    <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_400px] lg:items-start">
      <Card className="gap-6">
        <CardHeader>
          <CardTitle className="text-base">Data kendaraan</CardTitle>
          <CardDescription>Isi sesuai notis pajak atau data di sistem Samsat.</CardDescription>
        </CardHeader>
        <CardContent className="grid gap-6">
          <div className="grid gap-2">
            <Label htmlFor={`${id}-pokok`}>Pokok PKB</Label>
            <InputRupiah
              id={`${id}-pokok`}
              nilai={m.pokok}
              ubah={(pokok) => ubah({ pokok })}
              describedBy={`${id}-pokok-ket`}
            />
            <p id={`${id}-pokok-ket`} className="text-sm text-muted-foreground">
              Pajak pokok satu tahun, tanpa opsen dan denda.
            </p>
          </div>

          <div className="grid gap-2">
            <Label id={`${id}-jenis`}>Jenis kendaraan</Label>
            <RadioGroup
              value={m.jenis}
              onValueChange={(v) => ubah({ jenis: v as JenisKendaraan })}
              aria-labelledby={`${id}-jenis`}
              className="grid grid-cols-2 gap-2"
            >
              {(Object.keys(LABEL_KENDARAAN) as JenisKendaraan[]).map((j) => (
                <Label
                  key={j}
                  htmlFor={`${id}-${j}`}
                  className="items-start gap-3 rounded-lg border p-3 font-normal leading-snug has-[[data-state=checked]]:border-ring has-[[data-state=checked]]:bg-muted/50"
                >
                  <RadioGroupItem value={j} id={`${id}-${j}`} className="mt-0.5" />
                  <span className="grid gap-1">
                    <span className="font-medium">{LABEL_KENDARAAN[j]}</span>
                    <span className="text-xs text-muted-foreground">
                      SWDKLLJ {rupiah(j === "mobil" ? tarif.swdklljMobil : tarif.swdklljMotor)}
                    </span>
                  </span>
                </Label>
              ))}
            </RadioGroup>
          </div>

          <div className="grid gap-2">
            <Label htmlFor={`${id}-telat`}>Keterlambatan</Label>
            <div className="flex items-center gap-2">
              <Button
                type="button"
                variant="outline"
                size="icon"
                aria-label="Kurangi satu bulan"
                disabled={m.telat <= 0}
                onClick={() => ubah({ telat: Math.max(0, m.telat - 1) })}
              >
                <IconMinus />
              </Button>
              <Input
                id={`${id}-telat`}
                type="number"
                inputMode="numeric"
                min={0}
                max={MAKS_TELAT}
                value={m.telat}
                onChange={(e) => {
                  const n = e.target.valueAsNumber
                  ubah({ telat: Number.isFinite(n) ? Math.min(MAKS_TELAT, Math.max(0, Math.round(n))) : 0 })
                }}
                aria-describedby={`${id}-telat-ket`}
                className="w-20 text-center tabular-nums"
              />
              <Button
                type="button"
                variant="outline"
                size="icon"
                aria-label="Tambah satu bulan"
                disabled={m.telat >= MAKS_TELAT}
                onClick={() => ubah({ telat: Math.min(MAKS_TELAT, m.telat + 1) })}
              >
                <IconPlus />
              </Button>
              <span className="text-sm text-muted-foreground">bulan</span>
            </div>
            <p id={`${id}-telat-ket`} className="text-sm text-muted-foreground">
              Denda {persen(tarif.dendaPersenPerBulan)} per bulan dari pokok + opsen, paling banyak{" "}
              {tarif.maksBulanDenda} bulan.
              {kenaBatas && " Keterlambatan melewati batas, denda dihitung sampai batas."}
            </p>
          </div>

          <div className="flex items-start justify-between gap-4 rounded-lg border p-4">
            <div className="grid gap-1">
              <Label htmlFor={`${id}-amnesti`}>Terapkan amnesti denda</Label>
              <p id={`${id}-amnesti-ket`} className="text-sm text-muted-foreground">
                Selama program pemutihan berlaku, denda keterlambatan dihapus. Pokok, opsen, dan SWDKLLJ
                tetap dibayar.
              </p>
            </div>
            <Switch
              id={`${id}-amnesti`}
              checked={m.amnesti}
              onCheckedChange={(amnesti) => ubah({ amnesti })}
              aria-describedby={`${id}-amnesti-ket`}
            />
          </div>
        </CardContent>
      </Card>

      <Card className="gap-5 lg:sticky lg:top-4">
        <CardHeader>
          <CardDescription>Estimasi total bayar</CardDescription>
          <CardTitle>
            <output
              htmlFor={`${id}-pokok ${id}-telat ${id}-amnesti`}
              aria-live="polite"
              className="font-display text-4xl font-semibold tracking-tight tabular-nums"
            >
              {rupiah(h.total)}
            </output>
          </CardTitle>
          {m.amnesti && (
            <Badge variant="outline" className="mt-1 w-fit gap-1 px-1.5 text-muted-foreground">
              <IconRosetteDiscountCheck className="size-3.5 text-success" aria-hidden="true" />
              <span className="text-foreground/80">Masa amnesti</span>
            </Badge>
          )}
        </CardHeader>
        <CardContent className="grid gap-4">
          <dl className="grid gap-2 text-sm">
            <div className="flex justify-between gap-4">
              <dt className="text-muted-foreground">Pokok PKB</dt>
              <dd className="tabular-nums">{rupiah(h.pokok)}</dd>
            </div>
            <div className="flex justify-between gap-4 border-t pt-2">
              <dt className="text-muted-foreground">Opsen PKB {persen(tarif.opsenPersen)}</dt>
              <dd className="tabular-nums">{rupiah(h.opsen)}</dd>
            </div>
            <div className="flex justify-between gap-4 border-t pt-2">
              <dt className="text-muted-foreground">
                Denda {persen(tarif.dendaPersenPerBulan)} × {h.bulan} bulan
              </dt>
              <dd className="text-right tabular-nums">
                {m.amnesti && h.dendaAsli > 0 ? (
                  <>
                    <s className="mr-2 text-muted-foreground">
                      <span className="sr-only">semula </span>
                      {rupiah(h.dendaAsli)}
                    </s>
                    {rupiah(0)}
                  </>
                ) : (
                  rupiah(h.denda)
                )}
              </dd>
            </div>
            <div className="flex justify-between gap-4 border-t pt-2">
              <dt className="text-muted-foreground">SWDKLLJ {LABEL_KENDARAAN[m.jenis].toLowerCase()}</dt>
              <dd className="tabular-nums">{rupiah(h.swd)}</dd>
            </div>
          </dl>
          {m.amnesti && h.dendaAsli > 0 && (
            <p className="rounded-lg bg-success-soft p-3 text-sm">
              Wajib pajak hemat <strong className="tabular-nums">{rupiah(h.dendaAsli)}</strong> dari
              penghapusan denda.
            </p>
          )}
          <p className="text-xs text-muted-foreground">
            {tarif.catatan || "Estimasi. Tagihan resmi mengikuti notis pajak di loket."}
          </p>
        </CardContent>
        <CardFooter className="flex-wrap gap-2 border-t">
          <Button type="button" onClick={() => void salin()}>
            <IconCopy />
            Salin rincian
          </Button>
          <Button type="button" variant="ghost" onClick={() => setM(awal())}>
            <IconRestore />
            Atur ulang
          </Button>
        </CardFooter>
      </Card>
    </div>
  )
}

export default function HalamanKalkulator() {
  const { data: tarif, isLoading } = usePengaturan("tarif")
  return (
    <Halaman>
      <PengantarHalaman deskripsi="Hitung estimasi tagihan PKB untuk wajib pajak di loket, termasuk saat masa amnesti denda. Rumus dan parameternya sama dengan simulasi di portal.">
        <Button variant="outline" asChild>
          <Link to="/tarif">
            <IconAdjustments />
            Ubah parameter
          </Link>
        </Button>
      </PengantarHalaman>
      {isLoading || !tarif ? (
        <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_400px]">
          <Skeleton className="h-[30rem] rounded-xl" />
          <Skeleton className="h-80 rounded-xl" />
        </div>
      ) : (
        <Kalkulator tarif={tarif} />
      )}
    </Halaman>
  )
}
